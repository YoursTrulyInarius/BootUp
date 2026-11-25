(() => {
    const MAX_FILE_BYTES = 20 * 1024 * 1024;
    const MAX_TEXT_LENGTH = 1000000;
    const MAX_ARCHIVE_ENTRIES = 5000;
    const MAX_ARCHIVE_ENTRY_SIZE = 4 * 1024 * 1024;
    const MAX_ARCHIVE_TEXT_SIZE = 8 * 1024 * 1024;
    const JSZIP_URL = 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js';
    const PDFJS_VERSION = '4.10.38';
    const supportedExtensions = new Set(['pdf', 'pptx', 'docx', 'txt', 'md', 'csv']);
    let jszipPromise;
    let pdfjsPromise;
    const stopWords = new Set([
        'about', 'after', 'again', 'also', 'among', 'because', 'before', 'being', 'between',
        'both', 'could', 'does', 'during', 'each', 'from', 'have', 'having', 'into', 'more',
        'most', 'other', 'over', 'same', 'should', 'some', 'such', 'than', 'that', 'their',
        'there', 'these', 'they', 'this', 'those', 'through', 'under', 'until', 'very', 'were',
        'what', 'when', 'where', 'which', 'while', 'with', 'would', 'your', 'using', 'used',
        'will', 'been', 'then', 'them', 'only', 'many', 'much', 'make', 'made',
    ]);

    function extensionOf(name) {
        return name.includes('.') ? name.split('.').pop().toLowerCase() : '';
    }

    function validateFile(file) {
        if (!file) throw new Error('Choose a file first.');
        if (!supportedExtensions.has(extensionOf(file.name))) {
            throw new Error('Choose a PDF, PPTX, DOCX, TXT, Markdown, or CSV file.');
        }
        if (file.size === 0) throw new Error('The selected file is empty.');
        if (file.size > MAX_FILE_BYTES) throw new Error('Files must be 20 MB or smaller.');
    }

    function normalizeText(text) {
        return text
            .replace(/\u0000/g, '')
            .replace(/\r\n?/g, '\n')
            .replace(/[ \t]+/g, ' ')
            .replace(/\n{3,}/g, '\n\n')
            .trim()
            .slice(0, MAX_TEXT_LENGTH);
    }

    async function readOfficeText(file, extension) {
        const JSZip = await loadJsZip();
        const bytes = new Uint8Array(await file.slice(0, 4).arrayBuffer());
        if (bytes[0] !== 0x50 || bytes[1] !== 0x4b) {
            throw new Error('This file is not a valid PPTX or DOCX archive.');
        }

        const archive = await JSZip.loadAsync(file);
        const entries = Object.entries(archive.files).filter(([, entry]) => !entry.dir);
        if (entries.length > MAX_ARCHIVE_ENTRIES) throw new Error('This Office file contains too many parts to safely read.');

        const wanted = extension === 'pptx'
            ? entries
                .map(([name, entry]) => ({ name, entry, match: name.match(/^ppt\/slides\/slide(\d+)\.xml$/) }))
                .filter(item => item.match)
                .sort((a, b) => Number(a.match[1]) - Number(b.match[1]))
            : entries
                .filter(([name]) => name === 'word/document.xml')
                .map(([name, entry]) => ({ name, entry }));
        if (!wanted.length) throw new Error('No readable slide or document text was found in this file.');
        if (wanted.length > 200) throw new Error('This file has more than 200 slides or documents to process at once.');

        let declaredSize = 0;
        const xmlParts = [];
        for (const { entry } of wanted) {
            const size = entry._data?.uncompressedSize;
            if (!Number.isSafeInteger(size) || size > MAX_ARCHIVE_ENTRY_SIZE) {
                throw new Error('A document section is too large to safely process.');
            }
            declaredSize += size;
            if (declaredSize > MAX_ARCHIVE_TEXT_SIZE) throw new Error('The extracted document content is too large to process.');
            xmlParts.push(await entry.async('string'));
        }
        return xmlParts.map(extractXmlText).join('\n');
    }

    function loadJsZip() {
        if (window.JSZip) return Promise.resolve(window.JSZip);
        if (!jszipPromise) {
            jszipPromise = new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = JSZIP_URL;
                script.onload = () => {
                    if (!window.JSZip) {
                        jszipPromise = null;
                        reject(new Error('The Office document reader could not be loaded. Check your connection and retry.'));
                        return;
                    }
                    resolve(window.JSZip);
                };
                script.onerror = () => {
                    jszipPromise = null;
                    script.remove();
                    reject(new Error('The Office document reader could not be loaded. Check your connection and retry.'));
                };
                document.head.append(script);
            });
        }
        return jszipPromise;
    }

    function extractXmlText(source) {
        const xml = new DOMParser().parseFromString(source, 'application/xml');
        if (xml.getElementsByTagName('parsererror').length) {
            throw new Error('A slide or document section could not be read.');
        }
        const paragraphs = Array.from(xml.getElementsByTagNameNS('*', 'p'))
            .map(paragraph => Array.from(paragraph.getElementsByTagNameNS('*', 't'))
                .map(run => run.textContent || '')
                .join(''))
            .filter(text => text.trim() !== '');
        if (paragraphs.length) return paragraphs.join('\n');
        return Array.from(xml.getElementsByTagNameNS('*', 't'))
            .map(run => run.textContent || '')
            .join(' ');
    }

    async function readPdfText(file) {
        const bytes = new Uint8Array(await file.slice(0, 5).arrayBuffer());
        if (new TextDecoder().decode(bytes) !== '%PDF-') throw new Error('This file is not a valid PDF.');

        const pdfjsLib = await loadPdfjs();
        const pdf = await pdfjsLib.getDocument({ data: new Uint8Array(await file.arrayBuffer()) }).promise;
        try {
            if (pdf.numPages > 200) throw new Error('PDFs must have 200 pages or fewer.');
            const pages = [];
            for (let pageNumber = 1; pageNumber <= pdf.numPages; pageNumber += 1) {
                const page = await pdf.getPage(pageNumber);
                const content = await page.getTextContent();
                pages.push(content.items.map(item => item.str || '').join(' '));
                if (pages.join('\n').length > MAX_TEXT_LENGTH) {
                    throw new Error('The extracted PDF text is too large to process.');
                }
            }
            return pages.join('\n');
        } finally {
            await pdf.destroy();
        }
    }

    async function loadPdfjs() {
        if (!pdfjsPromise) {
            const baseUrl = `https://cdnjs.cloudflare.com/ajax/libs/pdf.js/${PDFJS_VERSION}`;
            pdfjsPromise = import(`${baseUrl}/pdf.min.mjs`)
                .then(pdfjsLib => {
                    pdfjsLib.GlobalWorkerOptions.workerSrc = `${baseUrl}/pdf.worker.min.mjs`;
                    return pdfjsLib;
                })
                .catch(() => {
                    pdfjsPromise = null;
                    throw new Error('The PDF reader could not be loaded. Check your connection and retry.');
                });
        }
        return pdfjsPromise;
    }

    async function extractText(file) {
        validateFile(file);
        const extension = extensionOf(file.name);
        let text;
        if (extension === 'pdf') {
            text = await readPdfText(file);
        } else if (extension === 'pptx' || extension === 'docx') {
            text = await readOfficeText(file, extension);
        } else {
            text = await file.text();
        }
        const normalized = normalizeText(text);
        if (!normalized) throw new Error('No selectable text was found in this file.');
        return normalized;
    }

    function sentenceList(text) {
        return text.split(/\n+/).flatMap(line => line.match(/[^.!?]+[.!?]?/g) || [])
            .map(sentence => sentence.replace(/^\s*(?:[-*•▪]\s*|\d+[.)]\s*)/, '').trim())
            .filter(sentence => sentence.length >= 30 && sentence.length <= 280);
    }

    function termsIn(text) {
        return text.match(/\p{L}[\p{L}'-]{4,}/gu) || [];
    }

    function escapeRegExp(text) {
        return text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function draftQuestions(text, points, limit) {
        const sentences = sentenceList(text);
        const counts = new Map();
        for (const sentence of sentences) {
            for (const term of termsIn(sentence)) {
                const normalized = term.toLocaleLowerCase();
                if (!stopWords.has(normalized)) counts.set(normalized, (counts.get(normalized) || 0) + 1);
            }
        }
        const repeatedTerms = [...counts.entries()]
            .filter(([, count]) => count > 1)
            .sort((a, b) => b[1] - a[1] || b[0].length - a[0].length)
            .map(([term]) => term);
        const rankedTerms = repeatedTerms.length
            ? repeatedTerms
            : [...counts.keys()].sort((a, b) => b.length - a.length);
        const questionLimit = Math.max(0, Math.min(100, Number.isInteger(limit) ? limit : 100));
        const questionPoints = Number.isInteger(points) && points >= 0 && points <= 100000 ? points : 1000;
        const drafts = [];
        const seen = new Set();

        for (const sentence of sentences) {
            if (drafts.length >= questionLimit) break;
            const duplicateKey = sentence.toLocaleLowerCase();
            if (seen.has(duplicateKey)) continue;

            const definition = sentence.match(/^(.{2,50}?)\s+(is|are|was|were|means|refers to|consists of|represents)\s+(.{10,190})$/i);
            let answer = '';
            let cloze = '';
            if (definition) {
                answer = definition[1].replace(/^(?:the|a|an)\s+/i, '').trim();
                cloze = sentence.replace(definition[1], '________');
            } else {
                for (const term of rankedTerms) {
                    const expression = new RegExp(`(^|[^\\p{L}\\p{N}])(${escapeRegExp(term)})(?=$|[^\\p{L}\\p{N}])`, 'iu');
                    if (expression.test(sentence)) {
                        answer = term;
                        cloze = sentence.replace(expression, '$1________');
                        break;
                    }
                }
            }
            if (answer.length < 2 || answer.length > 250 || cloze === sentence) continue;

            seen.add(duplicateKey);
            drafts.push({
                question_text: `Complete the statement: ${cloze}`,
                question_type: 'fill_blank',
                options: [],
                accepted_answers: [answer],
                answer: '',
                case_sensitive: false,
                explanation: 'Drafted from the imported source. Verify this answer before publishing.',
                timer_seconds: 0,
                points: questionPoints,
            });
        }
        return drafts;
    }

    window.bootUpFileImporter = { validateFile, extractText, draftQuestions, maxFileBytes: MAX_FILE_BYTES };
})();
