# Publishing System for "Vestnik NSU"

Our team presents a publishing system for the journal "Vestnik NSU", series "Information Technologies". The system replaces the journal's current website and guides an article from submission to publication. The journal is published in electronic form only.

The system is intended for an editorial office that currently has to select reviewers by hand, keep track of dozens of articles in different states, and prepare typesetting. It should remove the two main bottlenecks: delays caused by reviewers who do not respond, and the typesetting queue. The solution is built on the OJS platform: we deploy it, configure it for the editorial office's process, and extend it where the standard tools are not enough.

## Article Path

An article moves along the chain: **author → secretary → editorial board member → reviewer → editorial board meeting**.

1. The author submits the manuscript through the personal account. The submission form checks the required fields.
2. The secretary sees all incoming articles and distributes them among the editorial board members' portfolios by subject area.
3. The editorial board member studies the article. A weak or nonsensical article can be rejected immediately, without review. Otherwise, the board member selects a reviewer (one by default) from the pool.
4. The reviewer submits a review. If the board member disagrees with the review, they send the article to another reviewer.
5. The board member brings the article to the editorial board meeting.
6. The meeting makes a decision: accept or reject. It may send an article with a weak or disputed review for an additional review.
7. An accepted article goes through preparation for publication and is published in the journal.

Both returns (disagreement with a review and an additional review) lead back to the editorial board member: the next reviewer is always chosen by a person.

## Main System Features

- **Submission and admission** - the author uploads a manuscript in Word (DOCX) or LaTeX format and fills in the metadata and subject area through the personal account. The permitted formats are stated to the author explicitly; all others are prohibited. The submission form checks the required fields, and there is no return to the author for formal errors: at the entry stage an article can only be admitted for consideration or rejected.
- **Secretary and editorial board member roles** - the secretary sees all articles and the list of editorial board members and distributes articles among their portfolios. At the journal, the secretary is not a researcher, so they need to see immediately what an article is about; the secretary does not send articles to reviewers. An editorial board member sees only their own portfolio, "nothing extra".
- **Tags and reviewer pool** - articles, editorial board members, and reviewers all have tags. The system suggests tags and a person confirms them: the secretary confirms tags when distributing an article, and the board member may correct them. Reviewers are stored in a pool, so people who have been found are no longer forgotten and are available for repeat invitations. From the pool, the system suggests about 10 suitable candidates and shows why they are recommended; the choice is always made by the board member, who can also add a reviewer manually.
- **Number of reviewers** - one by default; the board member can add as many more as needed. The number is not strictly fixed, since practice differs between fields.
- **Review monitoring** - if a reviewer has not responded to an invitation, the system sends reminders on a 7/14/21 schedule: a reminder after one week, a second after two weeks, and on day 21 the reviewer receives a polite message about withdrawing their invitation, and the invitation is cancelled. The article is passed to another reviewer, chosen by the editorial board member.
- **Summary screen** - the secretary sees the status of all articles in the journal; an editorial board member sees the status of the articles in their portfolio: stage, responsible person, nearest deadline, time spent at the stage, and next action. Articles with missed deadlines are highlighted, and from the list one can go straight to the required action.
- **Editorial board meeting and decision** - the decision on an article is made by the editorial board meeting, not by a single editor. The system keeps the list of articles for the meeting and records the decisions made. Following the meeting, an article may be sent for an additional review. After the decision is made, the author receives it through the system.
- **Work through the personal account and notifications** - there is no submission by email. The author receives an email on every change of the article's status.
- **Revision by the author** - the author sends a new version and a response to the comments, after which a new round of review begins.
- **Typesetting** - after literary editing, the accepted manuscript is converted along the chain DOCX → LaTeX → JATS XML, and a PDF is built from the source. The result of automatic conversion is considered a draft and does not need to be brought to perfection. The checker sees the source, the LaTeX, and the compiled PDF side by side: errors are found in the PDF. The XML is validated against the schema, and errors go into a report.
- **Spelling and punctuation check** - the system only highlights possible errors and corrects nothing automatically, because a word may turn out to be a term. Formulas are left untouched.
- **Approval and publication** - the author is sent a PDF in the journal's layout and confirms it. The article is published as soon as it is ready in the current issue; when the deadline arrives, the issue is closed and the next one is opened. The decision to publish is made by the editorial board.
- **Statistics** - general statistics for the journal are kept. They will be refined after launch and at the editorial office's request.

## What We Will Need to Do During Development

- deploy OJS 3.5.0-x (without upgrading to 3.6) and configure it for the editorial office's process: the secretary and editorial board member roles, portfolios, the transition scheme, and blind review;
- implement what OJS does not have: the tag dictionary and tag suggestion, the reviewer pool and candidate hints, a hint for the secretary on distributing articles, the 7/14/21 reminder scheme with a polite withdrawal and cancellation of the invitation, and the editorial board meeting stage;
- build DOCX → LaTeX → JATS XML conversion as a separate service (Pandoc and XeLaTeX in its own container), connected to OJS by a connector plugin, because OJS natively produces JATS with an empty body; set up validation of the resulting XML and PDF building;
- migrate the archive of the old website;
- prepare deployment instructions and begin training the editorial staff;
- move the editorial office to the new system and decommission the old website.

### Technical Decisions

- Tags are stored in the plugin separately from the author's keywords. We do not change the OJS submission wizard.
- By December, tag suggestion is built from a dictionary with synonyms (title, abstract, keywords). After December, the dictionary can be replaced with a local embedding model. An external LLM via API is used only with the editorial office's consent.
- Environment: OJS 3.5.0-x, MariaDB 10.11, Docker Compose, a shared Google account for email, and a reference database with the journal settings stored in git.

## What Will Be Ready for the December Demo and MVP

The main result: an article can be taken through the system from submission to publication:

- **Platform:** OJS on the server, secretary and editorial board member roles, email, a conversion button in the article card, backups.
- **Tags and selection:** a tag dictionary, tag suggestion with confirmation by a person, up to 10 candidates from the pool for the editorial board member.
- **Deadlines and process:** a new diagram and requirements, 7/14/21 reminders with a polite withdrawal, a page for the secretary to distribute articles.
- **Conversion:** DOCX or LaTeX to LaTeX, PDF, and JATS XML as a draft, safe compilation, a table of results on 3-5 real articles.

The system should also become a foundation for further development. Possible extensions:

- support for other series of the journal on the same platform;
- access via NSU ID;
- control of the editorial process, including more advanced delay control;
- the ability to edit files in the system's web interface;
- reviewer selection and tags based on embeddings and, with the editorial office's consent, an LLM.

The result of the project will be a working journal website with real typesetting and reviewer selection.
