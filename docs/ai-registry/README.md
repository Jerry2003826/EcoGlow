# AI Registry — Team 236 (Jiarui Li)

This tree is the GenAI footprint required by
*FIT3047/FIT3048 — Instructions in Using GenAI Tools (AI Registry)*.

The unit asks every team to create a folder **inside each deliverable category**
in the PGP (example: `Business Vision/BV AI Registry`) and to record material
GenAI use for **each member**.

## What to copy into the PGP

Copy each folder under `Jiarui_Li/` into the matching PGP deliverable folder.
Keep the inner folder name exactly (`BV AI Registry`, `Coding AI Registry`, …).

| This repo | Paste into PGP |
| --- | --- |
| `Jiarui_Li/BV AI Registry/` | `Business Vision/BV AI Registry/` |
| `Jiarui_Li/Requirements AI Registry/` | `Requirements / User Stories/Requirements AI Registry/` |
| `Jiarui_Li/Architecture AI Registry/` | `Architecture/Architecture AI Registry/` |
| `Jiarui_Li/Database AI Registry/` | `Database / ER Diagram/Database AI Registry/` |
| `Jiarui_Li/UIUX AI Registry/` | `UI/UX / Wireframes/UIUX AI Registry/` |
| `Jiarui_Li/Coding AI Registry/` | `Development / Source Code/Coding AI Registry/` |
| `Jiarui_Li/Testing AI Registry/` | `Test Cases/Testing AI Registry/` |
| `Jiarui_Li/Debugging AI Registry/` | `Development / Source Code/Debugging AI Registry/` |
| `Jiarui_Li/Security AI Registry/` | `Security/Security AI Registry/` |
| `Jiarui_Li/Report AI Registry/` | `Reports / Individual Reflections / Governance/Report AI Registry/` |

Also place `00-INDEX.md`, `TEMPLATE.md`, `DECLARATION.md`, and the Word file
`Team236-AI-Prompt-Register-Jiarui-Li.docx` in a top-level PGP folder such as
`AI Registry/` so assessors can open one compiled copy.

## Files

- `00-INDEX.md` — master table of all IDs
- `TEMPLATE.md` — blank template for other members
- `DECLARATION.md` — sign this after you have read every entry
- `Jiarui_Li/<Category> AI Registry/AI-Prompt-Register.md` — full entries
- `Team236-AI-Prompt-Register-Jiarui-Li.docx` — printable compiled register

## What was recorded

Material influence only: research, planning, requirements, architecture,
database, UI/UX, coding, tests, debugging, security review, and this register.
Tiny follow-ups (“make this tighter”) are grouped under one ID.
This copy also merges the 14/08 artefact-level Word file (Stripe sandbox,
Pay-button recovery, Admin Users folds, sidebar, account restyle, rejected
sidebar hacks, and AI-IND-001 for the reflective diary).

Prompts that were originally in Chinese are kept in the original wording,
with an English gloss, because the unit asks for the **full prompt used**.

## Rebuild

```bash
python3 docs/ai-registry/_build/build_markdown.py
python3 docs/ai-registry/_build/build_docx.py
```

## 中文说明（给组员）

单位要求：AI 只要实质影响了交付物，就要记；每人一本；按 PGP 交付物类别建文件夹。
这里已经按 Jiarui Li 的 Cursor 会话整理完毕。其他组员请复制 `TEMPLATE.md`，
只写自己的提示词，不要抄这份。提交前请本人阅读并在 `DECLARATION.md` 签名。
