using System.Text;
using System.Text.Json;
using DocumentFormat.OpenXml;
using DocumentFormat.OpenXml.Packaging;
using DocumentFormat.OpenXml.Wordprocessing;
using WpPageSize = DocumentFormat.OpenXml.Wordprocessing.PageSize;

internal static class Program
{
    private const string Navy = "1F3864";
    private const string Body = "333333";
    private const string Gray = "595959";
    private const string Band = "F2F2F2";
    private const string Line = "BFBFBF";
    private const string Soft = "D9D9D9";

    public static int Main(string[] args)
    {
        var repoRoot = FindRepoRoot();
        var jsonPath = Path.Combine(repoRoot, "docs", "ai-registry", "_data", "register.json");
        var outputPath = args.Length > 0
            ? args[0]
            : Path.Combine(repoRoot, "docs", "ai-registry", "Team236-AI-Prompt-Register-Jiarui-Li.docx");

        var data = JsonSerializer.Deserialize<RegisterFile>(
            File.ReadAllText(jsonPath),
            new JsonSerializerOptions { PropertyNameCaseInsensitive = true })
            ?? throw new InvalidOperationException("register.json is empty.");

        Directory.CreateDirectory(Path.GetDirectoryName(outputPath)!);
        Build(outputPath, data);
        Console.WriteLine("Wrote " + outputPath);
        return 0;
    }

    private static string FindRepoRoot()
    {
        var dir = new DirectoryInfo(AppContext.BaseDirectory);
        while (dir != null)
        {
            if (File.Exists(Path.Combine(dir.FullName, "composer.json"))
                && Directory.Exists(Path.Combine(dir.FullName, "docs")))
            {
                return dir.FullName;
            }

            dir = dir.Parent;
        }

        return Directory.GetCurrentDirectory();
    }

    private static void Build(string outputPath, RegisterFile data)
    {
        using var doc = WordprocessingDocument.Create(outputPath, WordprocessingDocumentType.Document);
        var main = doc.AddMainDocumentPart();
        main.Document = new Document(new Body());
        var body = main.Document.Body!;

        AddStyles(main);
        AddCoreProperties(doc, data.Member);

        var sectPr = new SectionProperties(
            new WpPageSize { Width = 11906U, Height = 16838U },
            new PageMargin
            {
                Top = 1440,
                Bottom = 1440,
                Left = 1440U,
                Right = 1440U,
                Header = 720U,
                Footer = 720U,
                Gutter = 0U
            }
        );
        AddHeader(main, sectPr, "FIT3047/FIT3048  ·  Team 236  ·  AI Prompt Register");
        AddFooter(main, sectPr, data.Member.Name);

        AddParagraph(body, "FIT3047 / FIT3048 AI Prompt Register", "Title");
        AddParagraph(
            body,
            $"{data.Member.Unit}\n{data.Member.Team} — {data.Member.Client}\nMember: {data.Member.Name} ({data.Member.Email})\nPeriod: {data.Member.Period}\nCompiled: {data.Member.Compiled}",
            "Subtitle");

        AddParagraph(body, "1. Document control", "Heading1");
        body.Append(MakeTable(
            new[] { "Field", "Content" },
            data.DocumentControl.Select(r => new[] { r.Field, r.Content }).ToArray(),
            new[] { 2400, 6900 }));
        AddParagraph(body, "Table 1. Register metadata, including how the two source files were merged.", "Caption");

        AddParagraph(body, "2. Purpose", "Heading1");
        AddParagraph(
            body,
            "This register is the GenAI footprint required by FIT3047/FIT3048 Instructions in Using GenAI Tools (AI Registry). "
            + "The unit requires a folder inside each PGP deliverable category (for example Business Vision / BV AI Registry) "
            + "and a record for each team member. The goal is that AI-assisted work is transparent, reproducible, auditable, "
            + "academically defensible, and ethical.");
        AddParagraph(
            body,
            "An interaction is recorded when GenAI materially influenced an artefact: research, brainstorming, requirements, "
            + "user stories, architecture, database, UI/UX, coding, tests, debugging, security review, or report writing. "
            + "Follow-up micro-edits on the same artefact are grouped under one ID so the register does not invent extra sessions.");
        AddParagraph(
            body,
            "Prompts that were typed in Chinese are kept in the original wording, with an English gloss, because the unit asks for the full prompt used. "
            + "Sandbox keys and passwords are redacted. Other members must keep their own registers.");

        AddParagraph(body, "3. Prohibited use — how this register complies", "Heading1");
        body.Append(MakeTable(
            new[] { "Rule from the unit PDF", "How this member complied" },
            new[]
            {
                new[] { "Do not upload PII or confidential data", "No customer records were pasted into a tool. Sandbox/localhost test keys used for local setup are not written into this register." },
                new[] { "Do not present AI content as original research", "Vision Board interpretation (AI-025) is recorded as design reading, not as a substitute Business Vision." },
                new[] { "Do not use unverified AI references", "No AI-generated citations were added to project artefacts." },
                new[] { "Do not copy AI reports without review", "Code, CSS, and documents were reviewed by the student; several UI passes were rejected (AI-010, AI-020)." },
                new[] { "Do not fabricate tests, interviews, surveys, or sources", "PHPUnit was executed. Manual checks used a browser. No interviews or surveys were invented." },
            }));
        AddParagraph(body, "Table 2. Mapping of the unit’s prohibited-use list to this register.", "Caption");

        AddParagraph(body, "4. Master index", "Heading1");
        var projectEntries = data.Entries.OrderBy(e => e.Id).ToList();
        var indexRows = projectEntries.Select(e => new[]
        {
            e.Id,
            e.Date,
            ShortTool(e.Tool),
            e.Phase,
            CategoryTitle(data, e.Category),
            e.Purpose,
            ShortOutcome(e.Outcome),
        }).ToArray();
        body.Append(MakeTable(
            new[] { "ID", "Date", "Tool", "Phase", "Category", "Purpose", "Outcome" },
            indexRows,
            new[] { 900, 1200, 1400, 1200, 1400, 2600, 1400 }));
        AddParagraph(body, "Table 3. Material GenAI interactions for Jiarui Li (project artefacts).", "Caption");

        AddParagraph(body, "5. Entries by deliverable category", "Heading1");
        AddParagraph(
            body,
            "Copy each matching folder from docs/ai-registry/Jiarui_Li/ into the PGP deliverable folder and keep the inner name (BV AI Registry, Coding AI Registry, and so on).");

        foreach (var cat in data.Categories)
        {
            var entries = projectEntries.Where(e => e.Category == cat.Id).ToList();
            AddParagraph(body, $"{cat.RegistryFolder}", "Heading2");
            AddParagraph(body, $"PGP location: {cat.PgpFolder} / {cat.RegistryFolder}. Member: {data.Member.Name}.");

            if (entries.Count == 0)
            {
                AddParagraph(body, $"Nil return — no material GenAI use in this category during {data.Member.Period}.");
                continue;
            }

            foreach (var entry in entries)
            {
                AddParagraph(body, $"{entry.Id} — {entry.Purpose}", "Heading3");
                body.Append(MakeTable(
                    new[] { "Field", "Value" },
                    new[]
                    {
                        new[] { "ID", entry.Id },
                        new[] { "Date", entry.Date },
                        new[] { "Tool", entry.Tool },
                        new[] { "Project phase", entry.Phase },
                        new[] { "Purpose", entry.Purpose },
                    },
                    new[] { 2200, 7100 }));
                AddParagraph(body, "Prompt (as submitted)", "Heading4");
                AddParagraph(body, entry.Prompt);
                AddParagraph(body, "Response summary", "Heading4");
                AddParagraph(body, entry.Response);
                AddParagraph(body, "Validation performed", "Heading4");
                AddParagraph(body, entry.Validation);
                AddParagraph(body, "Outcome", "Heading4");
                AddParagraph(body, entry.Outcome);
            }
        }

        AddParagraph(body, "6. Nil returns", "Heading1");
        body.Append(MakeTable(
            new[] { "Category", "Statement" },
            data.NilReturns.Select(n => new[] { n.Category, n.Statement }).ToArray(),
            new[] { 2800, 6500 }));
        AddParagraph(body, "Table 4. Categories with no material GenAI use in this period.", "Caption");

        AddParagraph(body, "7. What was rejected", "Heading1");
        AddParagraph(
            body,
            "These AI suggestions were tried and then removed from the product, so they are not in the shipped artefacts. The surviving sidebar approach is recorded in AI-020.");
        foreach (var item in data.Rejected)
        {
            AddParagraph(body, "• " + item);
        }

        AddParagraph(body, "8. Individual assessment (not a project artefact)", "Heading1");
        AddParagraph(
            body,
            "The following interaction influenced a Moodle reflective diary, not Eco Glow source code. It is listed so the teaching team can see the AI trail. It must not be filed as a project artefact.");
        foreach (var entry in data.IndividualEntries)
        {
            AddParagraph(body, $"{entry.Id} — {entry.Purpose}", "Heading3");
            body.Append(MakeTable(
                new[] { "Field", "Value" },
                new[]
                {
                    new[] { "ID", entry.Id },
                    new[] { "Date", entry.Date },
                    new[] { "Tool", entry.Tool },
                    new[] { "Project phase", entry.Phase },
                    new[] { "Purpose", entry.Purpose },
                },
                new[] { 2200, 7100 }));
            AddParagraph(body, "Prompt (as submitted)", "Heading4");
            AddParagraph(body, entry.Prompt);
            AddParagraph(body, "Response summary", "Heading4");
            AddParagraph(body, entry.Response);
            AddParagraph(body, "Validation performed", "Heading4");
            AddParagraph(body, entry.Validation);
            AddParagraph(body, "Outcome", "Heading4");
            AddParagraph(body, entry.Outcome);
        }

        AddParagraph(body, "9. How other members add entries", "Heading1");
        AddParagraph(
            body,
            "Duplicate TEMPLATE.md. Use the next free ID in your own series (for example DS-AI-001). "
            + "Record the full prompt, a short summary, the check you actually performed, and Accepted / Modified / Rejected. "
            + "If the output cannot be validated, do not put it in a project artefact.");

        AddParagraph(body, "10. Declaration", "Heading1");
        AddParagraph(
            body,
            $"I, {data.Member.Name}, member of {data.Member.Team} ({data.Member.Unit}), declare that this register records the GenAI use that materially influenced artefacts I worked on between {data.Member.Period}; "
            + "that I reviewed AI output before it entered the application repository or PGP; "
            + "that I did not upload customer PII or confidential production credentials; "
            + "that I did not present AI text as original research or use unverified AI references; "
            + "that I did not fabricate test results, interviews, surveys, or academic sources; "
            + "that generated product images are disclosed as placeholders; "
            + "and that other members must keep their own registers.");
        AddParagraph(body, $"Name: {data.Member.Name}");
        AddParagraph(body, "Signature: ______________________________");
        AddParagraph(body, $"Date: {data.Member.Compiled}");

        body.Append(sectPr);
        main.Document.Save();
    }

    private static void AddCoreProperties(WordprocessingDocument doc, Member member)
    {
        var props = doc.AddCoreFilePropertiesPart();
        using var sw = new StreamWriter(props.GetStream(FileMode.Create, FileAccess.Write), Encoding.UTF8);
        sw.Write(
            """
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
              <dc:title>AI Prompt Register — Team 236</dc:title>
              <dc:subject>FIT3047/FIT3048 GenAI usage record</dc:subject>
              <dc:creator>{0}</dc:creator>
              <cp:lastModifiedBy>{0}</cp:lastModifiedBy>
              <cp:category>Project governance</cp:category>
            </cp:coreProperties>
            """.Replace("{0}", XmlEscape(member.Name)));
    }

    private static void AddStyles(MainDocumentPart main)
    {
        var part = main.AddNewPart<StyleDefinitionsPart>();
        var styles = new Styles();
        styles.Append(new DocDefaults(
            new RunPropertiesDefault(
                new RunPropertiesBaseStyle(
                    new RunFonts { Ascii = "Calibri", HighAnsi = "Calibri", EastAsia = "Calibri", ComplexScript = "Calibri" },
                    new FontSize { Val = "22" },
                    new FontSizeComplexScript { Val = "22" },
                    new Color { Val = Body },
                    new Languages { Val = "en-AU", EastAsia = "zh-CN" })),
            new ParagraphPropertiesDefault(
                new ParagraphPropertiesBaseStyle(
                    new SpacingBetweenLines { After = "160", Line = "276", LineRule = LineSpacingRuleValues.Auto }))));

        styles.Append(ParaStyle("Normal", "Normal", isDefault: true, ui: 0));
        styles.Append(HeadingStyle(1, "Calibri Light", "40", Navy, bold: false, before: "480", after: "120"));
        styles.Append(HeadingStyle(2, "Calibri Light", "32", Navy, bold: false, before: "360", after: "80"));
        styles.Append(HeadingStyle(3, "Calibri", "26", Navy, bold: true, before: "240", after: "80"));
        styles.Append(HeadingStyle(4, "Calibri", "22", Navy, bold: true, before: "160", after: "40"));

        styles.Append(new Style(
            new StyleName { Val = "Title" },
            new BasedOn { Val = "Normal" },
            new NextParagraphStyle { Val = "Normal" },
            new PrimaryStyle(),
            new StyleParagraphProperties(
                new Justification { Val = JustificationValues.Center },
                new SpacingBetweenLines { Before = "0", After = "120" }),
            new StyleRunProperties(
                new RunFonts { Ascii = "Calibri Light", HighAnsi = "Calibri Light" },
                new FontSize { Val = "48" },
                new FontSizeComplexScript { Val = "48" },
                new Color { Val = Navy }))
        { Type = StyleValues.Paragraph, StyleId = "Title" });

        styles.Append(new Style(
            new StyleName { Val = "Subtitle" },
            new BasedOn { Val = "Normal" },
            new NextParagraphStyle { Val = "Normal" },
            new StyleParagraphProperties(
                new Justification { Val = JustificationValues.Center },
                new SpacingBetweenLines { After = "240" }),
            new StyleRunProperties(
                new Color { Val = Gray },
                new FontSize { Val = "22" },
                new FontSizeComplexScript { Val = "22" }))
        { Type = StyleValues.Paragraph, StyleId = "Subtitle" });

        styles.Append(new Style(
            new StyleName { Val = "caption" },
            new BasedOn { Val = "Normal" },
            new UIPriority { Val = 35 },
            new PrimaryStyle(),
            new StyleParagraphProperties(new SpacingBetweenLines { Before = "40", After = "200" }),
            new StyleRunProperties(
                new Italic(),
                new Color { Val = Gray },
                new FontSize { Val = "18" },
                new FontSizeComplexScript { Val = "18" }))
        { Type = StyleValues.Paragraph, StyleId = "Caption" });

        part.Styles = styles;
        part.Styles.Save();
    }

    private static Style ParaStyle(string id, string name, bool isDefault, int ui)
    {
        return new Style(
            new StyleName { Val = name },
            new UIPriority { Val = ui },
            new PrimaryStyle())
        {
            Type = StyleValues.Paragraph,
            StyleId = id,
            Default = isDefault
        };
    }

    private static Style HeadingStyle(int level, string font, string size, string color, bool bold, string before, string after)
    {
        var rPr = new StyleRunProperties(
            new RunFonts { Ascii = font, HighAnsi = font, EastAsia = "Calibri", ComplexScript = font },
            new FontSize { Val = size },
            new FontSizeComplexScript { Val = size },
            new Color { Val = color });
        if (bold)
        {
            rPr.Append(new Bold());
        }

        return new Style(
            new StyleName { Val = $"heading {level}" },
            new BasedOn { Val = "Normal" },
            new NextParagraphStyle { Val = "Normal" },
            new UIPriority { Val = 9 },
            new PrimaryStyle(),
            new StyleParagraphProperties(
                new KeepNext(),
                new KeepLines(),
                new SpacingBetweenLines { Before = before, After = after },
                new OutlineLevel { Val = level - 1 }),
            rPr)
        {
            Type = StyleValues.Paragraph,
            StyleId = $"Heading{level}"
        };
    }

    private static void AddHeader(MainDocumentPart main, SectionProperties sectPr, string text)
    {
        var part = main.AddNewPart<HeaderPart>();
        part.Header = new Header(
            new Paragraph(
                new ParagraphProperties(
                    new Justification { Val = JustificationValues.Left },
                    new SpacingBetweenLines { After = "0" },
                    new ParagraphBorders(
                        new BottomBorder { Val = BorderValues.Single, Size = 6, Color = Line, Space = 4 })),
                new Run(
                    new RunProperties(
                        new RunFonts { Ascii = "Calibri", HighAnsi = "Calibri" },
                        new Color { Val = Gray },
                        new FontSize { Val = "18" }),
                    new Text(Safe(text)))));
        part.Header.Save();
        sectPr.Append(new HeaderReference { Type = HeaderFooterValues.Default, Id = main.GetIdOfPart(part) });
    }

    private static void AddFooter(MainDocumentPart main, SectionProperties sectPr, string name)
    {
        var part = main.AddNewPart<FooterPart>();
        var p = new Paragraph(
            new ParagraphProperties(
                new Justification { Val = JustificationValues.Right },
                new SpacingBetweenLines { After = "0" }));
        p.Append(new Run(
            new RunProperties(
                new Color { Val = Gray },
                new FontSize { Val = "18" }),
            new Text($"{name}  ·  Team 236  ·  ") { Space = SpaceProcessingModeValues.Preserve }));
        p.Append(new SimpleField(new Run(
            new RunProperties(
                new Color { Val = Gray },
                new FontSize { Val = "18" }),
            new Text("1")))
        { Instruction = " PAGE " });
        part.Footer = new Footer(p);
        part.Footer.Save();
        sectPr.Append(new FooterReference { Type = HeaderFooterValues.Default, Id = main.GetIdOfPart(part) });
    }

    private static void AddParagraph(Body body, string text, string styleId = "Normal")
    {
        var p = new Paragraph(new ParagraphProperties(new ParagraphStyleId { Val = styleId }));
        var lines = Safe(text).Replace("\r\n", "\n").Split('\n');
        for (var i = 0; i < lines.Length; i++)
        {
            if (i > 0)
            {
                p.Append(new Run(new Break()));
            }

            p.Append(new Run(new Text(lines[i]) { Space = SpaceProcessingModeValues.Preserve }));
        }

        body.Append(p);
    }

    private static Table MakeTable(string[] headers, string[][] rows, int[]? widths = null)
    {
        var table = new Table();
        table.Append(new TableProperties(
            new TableWidth { Width = "5000", Type = TableWidthUnitValues.Pct },
            new TableBorders(
                new TopBorder { Val = BorderValues.Single, Size = 8, Space = 0, Color = Line },
                new BottomBorder { Val = BorderValues.Single, Size = 8, Space = 0, Color = Line },
                new LeftBorder { Val = BorderValues.None, Size = 0, Space = 0, Color = "auto" },
                new RightBorder { Val = BorderValues.None, Size = 0, Space = 0, Color = "auto" },
                new InsideHorizontalBorder { Val = BorderValues.Single, Size = 4, Space = 0, Color = Soft },
                new InsideVerticalBorder { Val = BorderValues.None, Size = 0, Space = 0, Color = "auto" }),
            new TableCellMarginDefault(
                new TopMargin { Width = "40", Type = TableWidthUnitValues.Dxa },
                new StartMargin { Width = "60", Type = TableWidthUnitValues.Dxa },
                new BottomMargin { Width = "40", Type = TableWidthUnitValues.Dxa },
                new EndMargin { Width = "60", Type = TableWidthUnitValues.Dxa })));

        var grid = new TableGrid();
        if (widths == null)
        {
            var w = Math.Max(1, 9300 / headers.Length);
            widths = Enumerable.Repeat(w, headers.Length).ToArray();
        }

        foreach (var w in widths)
        {
            grid.Append(new GridColumn { Width = w.ToString() });
        }

        table.Append(grid);

        var headerRow = new TableRow(new TableRowProperties(new TableHeader()));
        for (var i = 0; i < headers.Length; i++)
        {
            headerRow.Append(Cell(headers[i], widths[i], header: true, band: false));
        }

        table.Append(headerRow);

        for (var r = 0; r < rows.Length; r++)
        {
            var row = new TableRow();
            for (var c = 0; c < headers.Length; c++)
            {
                var value = c < rows[r].Length ? rows[r][c] : "";
                row.Append(Cell(value, widths[c], header: false, band: r % 2 == 1));
            }

            table.Append(row);
        }

        return table;
    }

    private static TableCell Cell(string text, int width, bool header, bool band)
    {
        var tcPr = new TableCellProperties(
            new TableCellWidth { Width = width.ToString(), Type = TableWidthUnitValues.Dxa });
        if (header)
        {
            tcPr.Append(new TableCellBorders(
                new BottomBorder { Val = BorderValues.Single, Size = 8, Space = 0, Color = "999999" }));
            tcPr.Append(new Shading { Val = ShadingPatternValues.Clear, Color = "auto", Fill = "EEF2F6" });
        }
        else if (band)
        {
            tcPr.Append(new Shading { Val = ShadingPatternValues.Clear, Color = "auto", Fill = Band });
        }

        var pPr = new ParagraphProperties(new SpacingBetweenLines { After = "0", Line = "240", LineRule = LineSpacingRuleValues.Auto });
        var runPr = new RunProperties(new FontSize { Val = header ? "20" : "18" }, new FontSizeComplexScript { Val = header ? "20" : "18" });
        if (header)
        {
            runPr.Append(new Bold());
            runPr.Append(new Color { Val = Navy });
        }

        return new TableCell(
            tcPr,
            new Paragraph(pPr, new Run(runPr, new Text(Safe(text)) { Space = SpaceProcessingModeValues.Preserve })));
    }

    private static string CategoryTitle(RegisterFile data, string id)
    {
        return data.Categories.FirstOrDefault(c => c.Id == id)?.RegistryFolder ?? id;
    }

    private static string ShortTool(string tool)
    {
        if (tool.Contains("image", StringComparison.OrdinalIgnoreCase))
        {
            return "Cursor image gen.";
        }

        if (tool.Contains("Trello", StringComparison.OrdinalIgnoreCase))
        {
            return "Cursor + Trello";
        }

        return "Cursor";
    }

    private static string ShortOutcome(string outcome)
    {
        if (outcome.StartsWith("Accepted", StringComparison.OrdinalIgnoreCase))
        {
            return "Accepted";
        }

        if (outcome.StartsWith("Rejected", StringComparison.OrdinalIgnoreCase))
        {
            return "Rejected";
        }

        return "Modified";
    }

    private static string Safe(string text)
    {
        if (string.IsNullOrEmpty(text))
        {
            return "";
        }

        var sb = new StringBuilder(text.Length);
        foreach (var ch in text)
        {
            if (ch == '\n' || ch == '\r' || ch == '\t' || ch >= 32)
            {
                sb.Append(ch);
            }
        }

        return sb.ToString();
    }

    private static string XmlEscape(string text)
    {
        return text.Replace("&", "&amp;").Replace("<", "&lt;").Replace(">", "&gt;").Replace("\"", "&quot;");
    }

    private sealed class RegisterFile
    {
        public Member Member { get; set; } = new();
        public List<ControlRow> DocumentControl { get; set; } = new();
        public List<Category> Categories { get; set; } = new();
        public List<NilReturn> NilReturns { get; set; } = new();
        public List<string> Rejected { get; set; } = new();
        public List<Entry> IndividualEntries { get; set; } = new();
        public List<Entry> Entries { get; set; } = new();
    }

    private sealed class Member
    {
        public string Name { get; set; } = "";
        public string Email { get; set; } = "";
        public string Team { get; set; } = "";
        public string Unit { get; set; } = "";
        public string Client { get; set; } = "";
        public string Period { get; set; } = "";
        public string Compiled { get; set; } = "";
    }

    private sealed class ControlRow
    {
        public string Field { get; set; } = "";
        public string Content { get; set; } = "";
    }

    private sealed class Category
    {
        public string Id { get; set; } = "";
        public string PgpFolder { get; set; } = "";
        public string RegistryFolder { get; set; } = "";
        public string Title { get; set; } = "";
    }

    private sealed class NilReturn
    {
        public string Category { get; set; } = "";
        public string Statement { get; set; } = "";
    }

    private sealed class Entry
    {
        public string Id { get; set; } = "";
        public string Date { get; set; } = "";
        public string Tool { get; set; } = "";
        public string Phase { get; set; } = "";
        public string Purpose { get; set; } = "";
        public string Category { get; set; } = "";
        public string Prompt { get; set; } = "";
        public string Response { get; set; } = "";
        public string Validation { get; set; } = "";
        public string Outcome { get; set; } = "";
    }
}
