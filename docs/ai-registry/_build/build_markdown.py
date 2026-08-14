"""Render the AI Prompt Register markdown tree from register_data.py."""

from __future__ import annotations

import json
import sys
from collections import defaultdict
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(Path(__file__).resolve().parent))

from register_data import (  # noqa: E402
    CATEGORIES,
    DOCUMENT_CONTROL,
    ENTRIES,
    INDIVIDUAL_ENTRIES,
    MEMBER,
    NIL_RETURNS,
    REJECTED,
)


def _md_cell(text: str) -> str:
    return text.replace("|", "\\|").replace("\n", "<br>")


def entry_block(entry: dict) -> str:
    return "\n".join(
        [
            f"### {entry['id']} — {entry['purpose']}",
            "",
            "| Field | Value |",
            "| --- | --- |",
            f"| ID | {entry['id']} |",
            f"| Date | {entry['date']} |",
            f"| Tool | {_md_cell(entry['tool'])} |",
            f"| Project phase | {entry['phase']} |",
            f"| Purpose | {_md_cell(entry['purpose'])} |",
            f"| Outcome | {_md_cell(entry['outcome'])} |",
            "",
            "**Prompt (as submitted)**",
            "",
            entry["prompt"],
            "",
            "**Response summary**",
            "",
            entry["response"],
            "",
            "**Validation performed**",
            "",
            entry["validation"],
            "",
        ]
    )


def write(path: Path, text: str) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(text.rstrip() + "\n", encoding="utf-8")


def build() -> None:
    all_entries = sorted(ENTRIES, key=lambda e: e["id"])
    by_cat: dict[str, list] = defaultdict(list)
    for entry in all_entries:
        by_cat[entry["category"]].append(entry)

    cats = {c["id"]: c for c in CATEGORIES}
    member_root = ROOT / "Jiarui_Li"

    # Per-category registers
    for cat in CATEGORIES:
        entries = by_cat.get(cat["id"], [])
        lines = [
            f"# {cat['registry_folder']}",
            "",
            f"**Deliverable category (PGP):** {cat['pgp_folder']}  ",
            f"**Member:** {MEMBER['name']}  ",
            f"**Team:** {MEMBER['team']} — {MEMBER['unit']}  ",
            f"**Compiled:** {MEMBER['compiled']}",
            "",
            "This folder is the GenAI footprint for this deliverable category, as required by",
            "*FIT3047/FIT3048 — Instructions in Using GenAI Tools (AI Registry)*.",
            "Copy the folder into the matching PGP deliverable directory and keep the folder name.",
            "",
        ]
        if entries:
            lines.append("## Entries")
            lines.append("")
            for entry in entries:
                lines.append(entry_block(entry))
        else:
            lines.extend(
                [
                    "## Nil return",
                    "",
                    f"{MEMBER['name']} did not use GenAI in a way that materially influenced",
                    f"the official {cat['title']} artefact during {MEMBER['period']}.",
                    "",
                ]
            )
        if cat["id"] == "report":
            lines.extend(
                [
                    "## Individual assessment (not a project artefact)",
                    "",
                    "The following interaction influenced a Moodle reflective diary, not Eco Glow source code.",
                    "It is listed so the teaching team can see the AI trail. Do not file it as a project artefact.",
                    "",
                ]
            )
            for entry in INDIVIDUAL_ENTRIES:
                lines.append(entry_block(entry))
        write(member_root / cat["registry_folder"] / "AI-Prompt-Register.md", "\n".join(lines))

    # Index
    index_rows = [
        "| ID | Date | Tool | Phase | Category | Purpose | Outcome |",
        "| --- | --- | --- | --- | --- | --- | --- |",
    ]
    for entry in all_entries:
        cat = cats[entry["category"]]
        index_rows.append(
            "| {id} | {date} | {tool} | {phase} | {cat} | {purpose} | {outcome} |".format(
                id=entry["id"],
                date=entry["date"],
                tool=_md_cell(entry["tool"]),
                phase=entry["phase"],
                cat=cat["registry_folder"],
                purpose=_md_cell(entry["purpose"]),
                outcome=_md_cell(entry["outcome"]),
            )
        )

    nil_lines = ["| Category | Statement |", "| --- | --- |"]
    for item in NIL_RETURNS:
        nil_lines.append(f"| {item['category']} | {_md_cell(item['statement'])} |")

    control_lines = ["| Field | Content |", "| --- | --- |"]
    for row in DOCUMENT_CONTROL:
        control_lines.append(f"| {row['field']} | {_md_cell(row['content'])} |")

    rejected_lines = [f"- {item}" for item in REJECTED]
    individual_blocks = [entry_block(entry) for entry in INDIVIDUAL_ENTRIES]

    write(
        ROOT / "00-INDEX.md",
        "\n".join(
            [
                "# AI Prompt Register — master index",
                "",
                f"**Member:** {MEMBER['name']} ({MEMBER.get('email', '')})  ",
                f"**Team:** {MEMBER['team']}  ",
                f"**Unit:** {MEMBER['unit']}  ",
                f"**Client:** {MEMBER['client']}  ",
                f"**Period covered:** {MEMBER['period']}  ",
                f"**Primary tool:** {MEMBER['primary_tool']}  ",
                f"**Compiled:** {MEMBER['compiled']}",
                "",
                "Merged from the chronological PGP register and the 14/08 artefact-level Word file.",
                "Each project row is a material GenAI interaction. Detail lives in the category folder.",
                "",
                "## Document control",
                "",
                *control_lines,
                "",
                "## Register",
                "",
                *index_rows,
                "",
                "## Nil returns",
                "",
                *nil_lines,
                "",
                "## What was rejected",
                "",
                "These AI suggestions were tried and then removed from the product:",
                "",
                *rejected_lines,
                "",
                "The surviving sidebar approach is recorded in AI-020.",
                "",
                "## Individual assessment (not a project artefact)",
                "",
                "The following interaction influenced a Moodle reflective diary, not Eco Glow source code.",
                "",
                *individual_blocks,
            ]
        ),
    )

    write(
        ROOT / "TEMPLATE.md",
        "\n".join(
            [
                "# AI Prompt Register — teammate template",
                "",
                "Each member maintains their own register. Do not copy another member’s entries.",
                "Create `TeamMember_Name/<Category> AI Registry/AI-Prompt-Register.md` and add a row here.",
                "",
                "## Required fields (from the unit instructions)",
                "",
                "| ID | Date | Tool | Project Phase | Purpose |",
                "| --- | --- | --- | --- | --- |",
                "| AI-001 | DD/MM/YYYY | e.g. Copilot / Cursor / ChatGPT | Analysis / Design / Development / Testing | Short purpose |",
                "",
                "| Prompt | Response summary | Validation performed | Outcome |",
                "| --- | --- | --- | --- |",
                "| Full prompt used | Short summary of the AI output | How the output was checked | Accepted / Modified / Rejected |",
                "",
                "## Worked example (from the unit PDF, not our project)",
                "",
                "**Prompt**  ",
                "Generate functional requirements for a university event management system that allows",
                "students to register for events and administrators to manage bookings.",
                "",
                "**AI output summary**  ",
                "Generated 12 functional requirements including event creation, event registration,",
                "attendance tracking and reporting.",
                "",
                "**Validation**  ",
                "Compared requirements against stakeholder interview notes. Removed three requirements",
                "that were outside project scope.",
                "",
                "**Outcome**  ",
                "Modified and adopted.",
                "",
                "## Rules",
                "",
                "- Record the interaction when AI materially influenced the artefact.",
                "- Do not upload PII or confidential client data into a tool.",
                "- Do not present AI text as original research.",
                "- Do not use unverified AI references.",
                "- Do not fabricate tests, interviews, surveys, or sources.",
                "- If the output cannot be validated, do not put it in a project artefact.",
                "",
            ]
        ),
    )

    write(
        ROOT / "DECLARATION.md",
        "\n".join(
            [
                "# Declaration — GenAI use",
                "",
                f"I, **{MEMBER['name']}**, member of {MEMBER['team']} ({MEMBER['unit']}), declare that:",
                "",
                "1. This register records the GenAI use that materially influenced artefacts I worked on between "
                f"{MEMBER['period']}.",
                "2. I reviewed AI output before it entered the application repository or PGP.",
                "3. I did not upload customer PII or confidential production credentials into a GenAI tool.",
                "   Sandbox / localhost test keys used for local setup are not recorded in this register.",
                "4. I did not present AI-generated text as original research, and I did not use unverified AI references.",
                "5. I did not use GenAI to fabricate test results, interviews, surveys, or academic sources.",
                "6. Generated product images are disclosed as generated placeholders, not studio photography.",
                "7. Material that could not be validated was not included in project artefacts.",
                "8. Other members must keep their own registers for their own tool use.",
                "",
                f"Name: {MEMBER['name']}  ",
                "Signature: ______________________________  ",
                f"Date: {MEMBER['compiled']}",
                "",
            ]
        ),
    )

    write(
        ROOT / "README.md",
        "\n".join(
            [
                "# AI Registry — Team 236 (Jiarui Li)",
                "",
                "This tree is the GenAI footprint required by",
                "*FIT3047/FIT3048 — Instructions in Using GenAI Tools (AI Registry)*.",
                "",
                "The unit asks every team to create a folder **inside each deliverable category**",
                "in the PGP (example: `Business Vision/BV AI Registry`) and to record material",
                "GenAI use for **each member**.",
                "",
                "## What to copy into the PGP",
                "",
                "Copy each folder under `Jiarui_Li/` into the matching PGP deliverable folder.",
                "Keep the inner folder name exactly (`BV AI Registry`, `Coding AI Registry`, …).",
                "",
                "| This repo | Paste into PGP |",
                "| --- | --- |",
                *[
                    f"| `Jiarui_Li/{c['registry_folder']}/` | `{c['pgp_folder']}/{c['registry_folder']}/` |"
                    for c in CATEGORIES
                ],
                "",
                "Also place `00-INDEX.md`, `TEMPLATE.md`, `DECLARATION.md`, and the Word file",
                "`Team236-AI-Prompt-Register-Jiarui-Li.docx` in a top-level PGP folder such as",
                "`AI Registry/` so assessors can open one compiled copy.",
                "",
                "## Files",
                "",
                "- `00-INDEX.md` — master table of all IDs",
                "- `TEMPLATE.md` — blank template for other members",
                "- `DECLARATION.md` — sign this after you have read every entry",
                "- `Jiarui_Li/<Category> AI Registry/AI-Prompt-Register.md` — full entries",
                "- `Team236-AI-Prompt-Register-Jiarui-Li.docx` — printable compiled register",
                "",
                "## What was recorded",
                "",
                "Material influence only: research, planning, requirements, architecture,",
                "database, UI/UX, coding, tests, debugging, security review, and this register.",
                "Tiny follow-ups (“make this tighter”) are grouped under one ID.",
                "This copy also merges the 14/08 artefact-level Word file (Stripe sandbox,",
                "Pay-button recovery, Admin Users folds, sidebar, account restyle, rejected",
                "sidebar hacks, and AI-IND-001 for the reflective diary).",
                "",
                "Prompts that were originally in Chinese are kept in the original wording,",
                "with an English gloss, because the unit asks for the **full prompt used**.",
                "",
                "## Rebuild",
                "",
                "```bash",
                "python3 docs/ai-registry/_build/build_markdown.py",
                "python3 docs/ai-registry/_build/build_docx.py",
                "```",
                "",
                "## 中文说明（给组员）",
                "",
                "单位要求：AI 只要实质影响了交付物，就要记；每人一本；按 PGP 交付物类别建文件夹。",
                "这里已经按 Jiarui Li 的 Cursor 会话整理完毕。其他组员请复制 `TEMPLATE.md`，",
                "只写自己的提示词，不要抄这份。提交前请本人阅读并在 `DECLARATION.md` 签名。",
                "",
            ]
        ),
    )

    payload = {
        "member": {
            "name": MEMBER["name"],
            "email": MEMBER.get("email", ""),
            "team": MEMBER["team"],
            "unit": MEMBER["unit"],
            "client": MEMBER["client"],
            "period": MEMBER["period"],
            "compiled": MEMBER["compiled"],
        },
        "documentControl": DOCUMENT_CONTROL,
        "categories": [
            {
                "id": c["id"],
                "pgpFolder": c["pgp_folder"],
                "registryFolder": c["registry_folder"],
                "title": c["title"],
            }
            for c in CATEGORIES
        ],
        "nilReturns": NIL_RETURNS,
        "rejected": REJECTED,
        "individualEntries": INDIVIDUAL_ENTRIES,
        "entries": all_entries,
    }
    (ROOT / "_data" / "register.json").write_text(
        json.dumps(payload, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )
    print(f"Wrote {len(all_entries)} entries into {ROOT}")


if __name__ == "__main__":
    build()
