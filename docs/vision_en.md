# Publishing System for "Vestnik NSU"

Our team presents a publishing system for the journal "Vestnik NSU", Information Technologies series. The system replaces the journal's current website and guides an article from submission to publication. The journal is published in electronic form only.

The system is intended for an editorial office that currently has to select reviewers manually, track dozens of articles in various states, and prepare typesetting. It should eliminate two main bottlenecks: delays caused by unresponsive reviewers, and the typesetting queue. The basis is the OJS platform: we deploy it, configure it for the editorial office's process, and extend it where the standard tools are insufficient.

Main features of the system:

- **Article submission** - the author uploads a manuscript in Word or LaTeX format and specifies subject tags.
- **Reviewer and article tags** - reviewers and articles have subject tags, which the system uses to select candidates.
- **Reviewer selection** - the system suggests candidates with matching tags; the editor chooses among them and sends invitations.
- **Response and deadline control** - if a reviewer does not respond, a reminder is sent after a timeout, and the editor sees that the next candidate needs to be invited; the system also sends reminders as the review deadline approaches.
- **Editor's summary screen** - an editorial board member sees the status of all their articles on a single screen. For each article, it is important to see the current stage and the action required next.
- **Reviewer workflow via email and personal account** - the reviewer accepts or declines an invitation and submits a review.
- **Typesetting** - an accepted manuscript is converted into structured XML in JATS format, and a PDF is built from the source.
- **Author approval** - the author checks the final typeset version and confirms it in the system.
- **Continuous publication** - the article is published on the website immediately after approval, without waiting for the issue to close.

During development, we will need to:

- deploy OJS and configure it for the editorial office's process;
- implement tags for reviewers and articles, and candidate selection based on them;
- implement timeouts, reminders, and the article status summary screen;
- ensure the reviewer can work via email and a personal account;
- configure and, if necessary, extend OJS's existing tools for converting Word and LaTeX manuscripts to JATS XML and building PDFs, as well as validation of the resulting XML;
- configure author approval of typesetting and continuous publication;
- move the editorial office to the new system and decommission the old website.

It is important to test in advance the conversion of real manuscripts to JATS XML: the quality of the result may depend on the structure of the source document, and some materials may require manual correction.

The system should also serve as a foundation for further development. Possible extensions:

- migrating the archive of the old website;
- support for other series of the journal on the same platform;
- the ability to handle an article entirely via email;
- plagiarism checking of manuscripts.

The result of the project will be a working journal website with real typesetting and reviewer selection.