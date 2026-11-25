# BootUp

## About the system

BootUp is a browser-based platform for creating and hosting quizzes, with exams planned as part of the admin tools. A host prepares questions, publishes a quiz, and opens a lobby. Players visit the public landing page and use the six-digit game PIN to join. The long-term goal is to support live, timed quiz sessions with scoring and results.

The codebase already has early foundations for host sign-in, quiz authoring, PIN-based lobbies, and player registration. Live synchronized gameplay, scoring, and results are not implemented yet.

## Tech stack

- **Backend:** PHP 8.2+ handles pages, validation, sessions, and API endpoints.
- **Database:** MySQL stores hosts, source-file metadata, quizzes, questions, game sessions, and players. PHP connects through PDO with the MySQL driver.
- **Frontend:** Bootstrap 5.3.9 (SRI-pinned CDN CSS) provides responsive layout and form primitives; a custom CSS/JavaScript design system supplies BootUp's pixel-art styling, light/dark themes, and interactions.
- **Local development:** XAMPP provides Apache, PHP, and MySQL.

## Development phases

1. **Landing page — current phase:** Build and refine the public landing page and its branding. This phase is limited to the landing experience.
2. **Admin — next phase:** Develop the admin dashboard and expand tools for creating and managing quizzes, exams, questions, settings, and publishing.
3. **Users — final phase:** Complete the player-facing experience, including joining and taking quizzes or exams, and viewing results.

## Run with XAMPP

1. Copy the project folder into `C:\xampp\htdocs\BootUp`.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Open phpMyAdmin, select **Import**, and import `database/bootup.sql`. If the database already exists, run the single `CREATE TABLE source_files` statement from that file once.
4. If your local MySQL credentials are not the XAMPP defaults, set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` for Apache's PHP process. Defaults are `127.0.0.1:3306`, database `bootup`, user `root`, and an empty password; use stronger credentials outside a local development environment.
5. Visit `http://localhost/BootUp/`. Players enter a PIN there. Hosts can choose **Host sign in** and create a host account from the sign-in page. Host registration is open to anyone who can access the site.

The web server must be configured to serve this project at `/BootUp`; if you use a different URL path, update the `/BootUp` links in the PHP pages and editor script.

## Quiz builder

- Quiz metadata includes title, description, visibility, and draft/published status.
- Quiz defaults include a 5–300 second timer (or 0 for no timer), base points, question/answer randomization, and leaderboard visibility.
- Each question can override its timer and points and include an answer explanation.
- Multiple choice supports 2–4 options and exactly one correct answer.
- True/false questions store the correct true/false value.
- Fill-in-the-blank questions accept one or more answers and optionally match case-sensitively.
- The host dashboard opens to a private study-file library with a side navigation for the dashboard, quizzes, and manual quiz creation. Hosts can upload PDF, PPTX, DOCX, TXT, Markdown, and CSV files (20 MB each; 100 MB total per host), then create a quiz, download, or delete a source file. Originals are stored outside the public web root and require the owning host account to access.
- The public, host, and player pages share a pixel-art light/dark mode switch. The selected theme is saved in the browser and carries across BootUp pages; the switch animates on activation.
- When a host creates a quiz from a source file, text extraction and draft generation happen in the browser. The file itself is not sent to parsing-library CDNs; only the edited quiz questions are sent to BootUp when saved. Drafts are editable fill-in-the-blank questions based on source statements and key terms. The quiz's default timer applies to all questions unless an individual question is overridden. PDF and Office parsing libraries load from cdnjs, so an internet connection is required for those formats. Scanned PDFs without selectable text are not supported.
- Changes are validated server-side and saved transactionally.
- Published quizzes can be hosted in a lobby with a unique six-digit PIN; hosts can close the lobby.
- Players can join an open lobby and register a unique player name.

## Existing implementation

The existing quiz editor supports multiple-choice, true/false, and fill-in-the-blank questions. Hosts can publish quizzes and open PIN-based lobbies, and players can register a name to join. The admin and user phases above still describe the planned development sequence; these early features do not mean those phases are complete. Avatar customization, live player-count updates, WebSocket hosting, synchronized gameplay/scoring/leaderboards, result exports, and load testing are not implemented yet; the 50-player acceptance criteria are not yet met.
