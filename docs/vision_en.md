# Publishing System for “Vestnik NSU”

Our team presents a publishing system for the journal “Vestnik NSU,” series “Information Technologies.” The system replaces the journal’s current website and manages an article from submission to publication. The journal is published in electronic form only.

The system is intended for the editorial office, which has to manually select reviewers, track dozens of articles in different states, and prepare layout. It must remove two main bottlenecks: delays caused by reviewers who do not respond, and the layout queue. The system is based on the OJS platform: we deploy it, configure it for the editorial process, and extend it where the standard tools are insufficient.

### Main capabilities of the system:

- **Submission and initial check** — the author uploads a manuscript in Word or LaTeX format, fills in metadata, and specifies subject tags; the system checks completeness. The editor admits the article for review, returns it to the author for correction, or rejects it with a reason.
- **Reviewer selection by tags** — articles and reviewers have tags from a common reference list. The system suggests candidates with matching tags, taking their workload into account, and shows why a candidate is recommended; the editor chooses from them or adds a reviewer manually. The final decision on appointment always remains with the editor.
- **Review monitoring** — if a reviewer does not respond, a reminder is sent after a timeout, and the editor sees that the next candidate needs to be invited. The editor can predefine the order of candidates, in which case the next invitation is sent automatically. The system reminds about the approaching review deadline and shows whether there are enough accepted reviewers and received reviews according to the journal’s rules.
- **Editor’s summary screen** — a member of the editorial board sees the state of all their articles on one screen: stage, responsible person, nearest deadline, time at the stage, and next action. Articles with violated deadlines are highlighted, and from the list one can go directly to the required action.
- **Work via email and personal account**
- **Editor’s decision and author’s revision** — based on the reviews, the handling editor accepts the article, returns it for revision, or rejects it with justification. The author sends a new version and a response to the comments, and a new round of review begins.
- **Layout** — after copy editing, the accepted manuscript is converted to JATS XML, and a PDF is assembled from the source. The XML is checked against the schema, errors are included in a report; the result can be corrected manually and uploaded instead of the automatic one.
- **Approval and publication** — the author checks the final layout and confirms it in the system or sends comments; confirmation is recorded for a specific version of the files. The article is published on the website immediately after approval, without waiting for the issue to be closed, or is assigned to an issue.
- **Roles, rights, blind peer review, and article history** — the system has author, handling editor, editor-in-chief, reviewer, technical editor, administrator, and reader. Actions are available only to participants with authority, the article state changes only according to the agreed transition scheme, and the identity of the author and reviewer is hidden from the other party according to the model adopted by the journal. For each article, versions, reviews, decisions, correspondence, and responsible persons are preserved.

### In the course of development, we will need to:

- deploy OJS and configure it for the editorial process, including roles, rights, the transition scheme, and blind peer review;
- implement what is not available in OJS: tags and reviewer selection, timeouts and invitation order, the summary screen, and work of participants via email;
- configure and, if necessary, extend the existing OJS tools for converting Word and LaTeX manuscripts to JATS XML and assembling PDF, as well as checking the resulting XML;
- migrate the archive of the old website;
- prepare deployment instructions and begin training editorial staff;
- transition the editorial office to the new system and decommission the old website.

By the end of the first semester, the system must be deployed on the agreed infrastructure, the necessary plugins installed and configured, and training of editorial staff started.

At the same time, it is important to check in advance the conversion of real manuscripts to JATS XML: the quality of the result may depend on the structure of the source document, and for some materials manual correction may be required.

## What will be ready for MVP

By MVP, the main result is: an article can be processed through the system from submission to publication.

The MVP will include:

- submission of a manuscript in Word or LaTeX format and initial check;
- tags and reviewer selection (partially);
- inviting reviewers and receiving reviews via email and personal account, timeouts for response, and reminders;
- the editor’s summary screen with article states and the next action;
- editorial decision, author’s revision, and a new round of review;
- copy editing, conversion of the manuscript to JATS XML, PDF assembly, and XML checking;
- approval of the layout with the author;
- publication of the article on the website, including continuous publication;
- the ability to manage an article entirely via email;
- roles and rights, blind peer review.

Several small functions may not be included in the MVP. They do not prevent an article from being processed through the entire process and will be completed for the final version:

- the missing part of tags and reviewer selection;
- some reminders (for example, about the approaching review deadline);
- migration of the archive of the old website.

## What will be ready in the final version

By the final version, all capabilities described above will be implemented and all work from the list “In the course of development” will be completed:

- everything included in the MVP, brought to a working state on real manuscripts;
- functions not included in the MVP, including migration of the archive of the old website;
- transition of the editorial office to the new system and decommissioning of the old website.

The system must also become a basis for further development. Possible extensions:

- support for other series of the journal on the same platform;
- access via nsu id;
- control of the editorial process;
- the ability to edit files in the system’s web interface;
- delay control.

The result of the project will be a working journal website with real layout and reviewer selection.