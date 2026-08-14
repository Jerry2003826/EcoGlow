#!/usr/bin/env python3
"""Build the compiled Word register from register.json."""

from __future__ import annotations

import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
PROJ = Path(__file__).resolve().parent / "docxgen" / "RegisterDoc.csproj"
OUT = ROOT / "Team236-AI-Prompt-Register-Jiarui-Li.docx"


def main() -> int:
    subprocess.check_call(
        ["dotnet", "run", "--project", str(PROJ), "-c", "Release", "--", str(OUT)],
    )
    extra = Path("/Users/lijiarui/Downloads/FIT3047_AI_Prompt_Register_Jiarui_Li.docx")
    extra.write_bytes(OUT.read_bytes())
    print(OUT)
    print(extra)
    return 0


if __name__ == "__main__":
    sys.exit(main())
