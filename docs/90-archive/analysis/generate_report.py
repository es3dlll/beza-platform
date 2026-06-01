#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Beza Platform — Comprehensive Documentation Report Generator
=============================================================
Reads docs/Beza-Platform/ metadata and generates an exhaustive
report of every file, folder, size, line count, first/last lines,
and GitHub links.

Usage:
    python generate_report.py

Output:
    BIZA-REPORT-FULL.md  (saved alongside this script)
"""

import os, json, re, urllib.parse
from collections import defaultdict
from datetime import datetime, timezone

# ── Configuration ──────────────────────────────────────────────────────────
ROOT = os.path.dirname(os.path.abspath(__file__))
META_FILE = r"C:\Users\xRoot\AppData\Local\Temp\opencode\final_meta.txt"
CONTENT_FILE = r"C:\Users\xRoot\AppData\Local\Temp\opencode\f5l5_output.txt"
GITHUB_BASE = "https://github.com/es3dlll/beza-platform/blob/main/docs/Beza-Platform/"
OUT_FILE = os.path.join(ROOT, "BIZA-REPORT-FULL.md")
NOW = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M UTC")

# ── Load data ──────────────────────────────────────────────────────────────
def load_meta(path):
    data = {}
    with open(path, "r", encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if not line:
                continue
            parts = line.split("|")
            if len(parts) == 3:
                rel, sz, lc = parts
                data[rel] = {"size": int(sz), "lines": int(lc)}
    return data

def load_content(path):
    data = {}
    with open(path, "r", encoding="utf-8") as f:
        for line in f:
            line = line.strip()
            if not line:
                continue
            idx1 = line.find("|")
            if idx1 == -1:
                continue
            rel = line[:idx1]
            rest = line[idx1 + 1:]
            idx2 = rest.find("|")
            if idx2 == -1:
                continue
            first5 = rest[:idx2].replace("\\n", "\n")
            last5 = rest[idx2 + 1:].replace("\\n", "\n")
            data[rel] = {"first5": first5, "last5": last5}
    return data

# ── Helpers ────────────────────────────────────────────────────────────────
def gh_link(rel_path):
    """Return a Markdown link to the file on GitHub."""
    url = GITHUB_BASE + rel_path.replace("\\", "/")
    return f"[{rel_path}]({url})"

def gh_link_raw(rel_path):
    """Return just the URL."""
    return GITHUB_BASE + rel_path.replace("\\", "/")

def dir_structure(files):
    """Build nested dict of directories."""
    tree = {}
    for rel in files:
        parts = rel.replace("\\", "/").split("/")
        node = tree
        for p in parts[:-1]:
            node = node.setdefault(p, {})
        node[parts[-1]] = rel
    return tree

def indent(level):
    return "  " * level

def extract_title(first5):
    """Extract the first heading from first5 lines."""
    for line in first5.split("\n"):
        line = line.strip()
        if line.startswith("#"):
            return line.lstrip("#").strip()
    return "(بدون عنوان)"

# ── Main Report Generation ────────────────────────────────────────────────
def generate():
    meta = load_meta(META_FILE)
    content = load_content(CONTENT_FILE)

    # Group by top-level folder
    groups = defaultdict(list)
    for rel in sorted(meta.keys()):
        if "\\" in rel:
            top = rel.split("\\")[0]
        else:
            top = "(root)"
        groups[top].append(rel)

    # Order: root files first, then directories
    top_order = ["(root)", "docs", "specs", "tasks", "examples"]

    lines = []
    L = lines.append

    # ── Header ──
    L(f"# تقرير بزا الشامل — Beza Platform Full Documentation Report")
    L(f"")
    L(f"**تاريخ التوليد:** {NOW}")
    L(f"**إجمالي الملفات:** {len(meta)}")
    L(f"**إجمالي الأسطر:** {sum(m['lines'] for m in meta.values()):,}")
    L(f"**إجمالي الحجم:** {sum(m['size'] for m in meta.values()):,} بايت ({sum(m['size'] for m in meta.values()) / 1024:.1f} KB)")
    L(f"**قاعدة GitHub:** [{GITHUB_BASE}]({GITHUB_BASE})")
    L(f"")
    L(f"---")
    L(f"")

    # ── Table of Contents ──
    L(f"## فهرس المحتويات")
    L(f"")
    for grp in top_order:
        if grp in groups:
            cnt = len(groups[grp])
            L(f"1. **{grp}/** — {cnt} ملف")
    L(f"")

    # ── Section per directory group ──
    section_num = 0
    for grp in top_order:
        if grp not in groups:
            continue
        rels = groups[grp]
        section_num += 1

        # Sub-group by second-level directory
        sub_groups = defaultdict(list)
        single_files = []
        for rel in rels:
            parts = rel.split("\\")
            if len(parts) >= 2 and parts[0] == grp:
                if len(parts) >= 3:
                    sub_groups[parts[1]].append(rel)
                else:
                    single_files.append(rel)
            elif grp == "(root)":
                single_files.append(rel)

        L(f"---")
        L(f"")
        L(f"# {section_num}. 📁 {grp}/ — {len(rels)} ملف — {sum(meta[r]['lines'] for r in rels):,} سطر")

        # Summary stats
        total_size = sum(meta[r]['size'] for r in rels)
        L(f"")
        L(f"| المؤشر | القيمة |")
        L(f"|--------|--------|")
        L(f"| عدد الملفات | {len(rels)} |")
        L(f"| إجمالي الأسطر | {sum(meta[r]['lines'] for r in rels):,} |")
        L(f"| إجمالي الحجم | {total_size:,} بايت ({total_size/1024:.1f} KB) |")
        L(f"| متوسط الأسطر/ملف | {sum(meta[r]['lines'] for r in rels)/len(rels):.0f} |")

        # Subdirectories
        for sub in sorted(sub_groups.keys()):
            sub_files = sub_groups[sub]
            L(f"")
            L(f"## {section_num}.{list(sub_groups.keys()).index(sub)+1}. 📂 **{grp}/{sub}/** — {len(sub_files)} ملف")
            L(f"")
            L(f"| # | الملف | الحجم | أسطر | الملخص |")
            L(f"|---|------|------|------|--------|")
            for i, rel in enumerate(sorted(sub_files), 1):
                m = meta[rel]
                c = content.get(rel, {"first5": "", "last5": ""})
                title = extract_title(c["first5"][:200])
                L(f"| {i} | {gh_link(rel)} | {m['size']:,} B | {m['lines']:,} | {title[:80]} |")

        # Single files at this level
        if single_files:
            L(f"")
            L(f"## {section_num}.A. 📄 ملفات المستوى الحالي")
            L(f"")
            L(f"| # | الملف | الحجم | أسطر | الملخص |")
            L(f"|---|------|------|------|--------|")
            for i, rel in enumerate(sorted(single_files), 1):
                m = meta[rel]
                c = content.get(rel, {"first5": "", "last5": ""})
                title = extract_title(c["first5"][:200])
                L(f"| {i} | {gh_link(rel)} | {m['size']:,} B | {m['lines']:,} | {title[:80]} |")

    # ── Cross-Reference Analysis ──
    L(f"")
    L(f"---")
    L(f"# تحليل الروابط المتقاطعة")
    L(f"")

    # Count references between files
    refs = defaultdict(list)
    for rel, c in content.items():
        text = c["first5"] + "\n" + c["last5"]
        # Find all references to other files in docs/
        for other_rel in meta:
            if other_rel == rel:
                continue
            other_name = other_rel.replace("\\", "/").split("/")[-1].replace(".md", "")
            if other_name in text and len(other_name) > 5:
                refs[rel].append(other_rel)

    L(f"إجمالي الملفات التي تحتوي روابط لمستندات أخرى: {len(refs)}")
    L(f"")
    L(f"### أهم الملفات المرجعية (الأكثر استشهاداً)")
    L(f"")

    # Count how many times each file is referenced
    cited = defaultdict(int)
    for rel, targets in refs.items():
        for t in targets:
            cited[t] += 1

    top_cited = sorted(cited.items(), key=lambda x: -x[1])[:20]
    L(f"| # | الملف | عدد المرات التي ذُكر فيها |")
    L(f"|---|------|---------------------------|")
    for i, (rel, count) in enumerate(top_cited, 1):
        L(f"| {i} | {gh_link(rel)} | {count} |")

    # ── Gap Analysis ──
    L(f"")
    L(f"---")
    L(f"# تحليل الفجوات (Gap Analysis)")
    L(f"")

    # Tasks with their specs status
    task_specs = {
        "tasks\\01-auth": "A1-register, A2-login, A3-otp, A4-logout, A5-2fa",
        "tasks\\02-wallet": "W1-create-wallet, W2-balance, W3-exchange",
        "tasks\\03-transactions": "T1-transfer, T2-transfer-qr, T3-request-money, T4-deposit-bank, T5-deposit-card, T6-withdraw-bank, T7-withdraw-agent, T8-deposit-agent, T9-pay-bills, T10-topup-phone",
        "tasks\\04-cards": "C1-issue-card, C2-card-manage, C3-card-reports, C4-apple-google-pay",
        "tasks\\05-merchants": "M1-register-merchant, M2-merchant-products, M3-payment-gateway, M4-merchant-orders, M5-merchant-recurring, M6-merchant-settlement, M7-qr-code-generation",
        "tasks\\06-agents": "AG1-register-agent, AG2-agent-dashboard, AG3-agent-settlement, AG4-agent-map",
        "tasks\\07-deals": "D1-create-deal, D2-invest-deal, D3-complete-deal, D4-cancel-deal",
        "tasks\\08-referral": "R1-referral",
        "tasks\\09-kyc": "K1-kyc",
        "tasks\\10-admin": "AD1-dashboard, AD2-user-management, AD3-merchant-agent-approval, AD4-reports, AD5-disputes, AD6-settings",
        "tasks\\11-storefront": "Storefront docs",
        "tasks\\12-landing": "L1-landing",
        "tasks\\13-infra": "I1-localhost-setup, I2-database-setup",
        "tasks\\14-security": "SE1-2fa, SE2-fraud-detection, SE3-audit-log",
        "tasks\\15-testing": "TST1-laravel-tests, TST2-flutter-tests, TST3-k6-load-tests",
        "tasks\\16-notifications": "N1-notifications",
        "tasks\\17-system": "SY1-install, SY2-health, SY3-manage, SY4-settings",
    }

    # SPEC status
    L(f"### حالة نقاط API في MERCHANT-API-SPEC.md")
    L(f"")
    spec_status = {
        "01-04 Auth (مصادقة)": "✅ موثقة",
        "05-07 Keys (مفاتيح)": "⬜ فارغة — تحتاج تعبئة",
        "08-10 Payment (دفع)": "⬜ فارغة — تحتاج تعبئة",
        "11-12 QR Code": "✅ موثقة (M7)",
        "13-14 Settlement (تسوية)": "✅ موثقة (M6)",
        "15-16 Reports (تقارير)": "⬜ فارغة — تحتاج تعبئة",
        "17 Manual Settlement": "✅ موثقة (M6)",
    }
    L(f"| النقطة | الحالة |")
    L(f"|--------|--------|")
    for point, status in spec_status.items():
        L(f"| {point} | {status} |")

    L(f"")
    L(f"### المهام المكتملة (Tasks with examples)")
    L(f"")
    L(f"| المجموعة | المهام | حالة examples/ | حالة tasks/ |")
    L(f"|---------|--------|--------------|------------|")

    for task_dir, modules in sorted(task_specs.items()):
        # Count files in examples/ that match this task
        example_count = sum(1 for r in meta if r.startswith("examples\\") and task_dir.split("\\")[-1] in r)
        task_count = sum(1 for r in meta if r.startswith(task_dir))
        example_status = "✅" if example_count > 0 else "⬜"
        task_status = "✅" if task_count > 0 else "⬜"
        L(f"| {task_dir} | {modules} | {example_status} ({example_count} ملف) | {task_status} ({task_count} ملف) |")

    # ── File Detail Section ──
    L(f"")
    L(f"---")
    L(f"# تفصيل كل ملف — Full File-by-File Breakdown")
    L(f"")

    for rel in sorted(meta.keys()):
        m = meta[rel]
        c = content.get(rel, {"first5": "", "last5": ""})
        folder = "\\".join(rel.split("\\")[:-1]) if "\\" in rel else "."
        fname = rel.split("\\")[-1]

        L(f"---")
        L(f"### {gh_link(rel)}")
        L(f"")
        L(f"| الحقل | القيمة |")
        L(f"|-------|--------|")
        L(f"| المسار الكامل | `{os.path.join(ROOT, rel)}` |")
        L(f" | رابط GitHub | [{GITHUB_BASE}{rel.replace(chr(92), '/')}]({GITHUB_BASE}{rel.replace(chr(92), '/')}) |")
        L(f"| الحجم | {m['size']:,} بايت |")
        L(f"| عدد الأسطر | {m['lines']:,} |")
        L(f"| المجلد | `{folder}` |")
        L(f"| اسم الملف | `{fname}` |")

        first_lines = c["first5"].strip()
        last_lines = c["last5"].strip()

        if first_lines:
            title = extract_title(first_lines)
            L(f"| العنوان المستخلص | {title[:120]} |")

        L(f"")
        L(f"#### أول 5 أسطر (افتتاحية)")
        L(f"")
        L(f"```")
        L(f"{first_lines[:600]}")
        L(f"```")
        L(f"")

        if last_lines:
            L(f"#### آخر 5 أسطر (ختامية)")
            L(f"")
            L(f"```")
            L(f"{last_lines[:600]}")
            L(f"```")
            L(f"")

    # ── Final Summary ──
    L(f"")
    L(f"---")
    L(f"# الخلاصة")
    L(f"")
    L(f"| المقياس | القيمة |")
    L(f"|--------|--------|")
    L(f"| إجمالي الملفات | {len(meta)} |")
    L(f"| إجمالي الأسطر | {sum(m['lines'] for m in meta.values()):,} |")
    L(f"| إجمالي الحجم | {sum(m['size'] for m in meta.values()):,} بايت ({sum(m['size'] for m in meta.values())/1024/1024:.1f} MB) |")
    L(f"| عدد المجلدات الفرعية | {len(set('/'.join(r.replace(chr(92), '/').split('/')[:-1]) for r in meta))} |")
    L(f"| الملفات ذات المحتوى الكامل | {sum(1 for r in meta if meta[r]['lines'] > 50)} |")
    L(f"| الملفات الصغيرة (< 10 أسطر) | {sum(1 for r in meta if meta[r]['lines'] < 10)} |")
    L(f"| أكبر ملف | {max(meta, key=lambda r: meta[r]['lines'])} ({max(meta[r]['lines'] for r in meta)} سطر) |")
    L(f"| تاريخ التقرير | {NOW} |")

    # Write output
    output = "\n".join(lines)
    with open(OUT_FILE, "w", encoding="utf-8") as f:
        f.write(output)

    print(f"[OK] Report generated: {OUT_FILE}")
    print(f"   Total files: {len(meta)}")
    print(f"   Total lines: {sum(m['lines'] for m in meta.values()):,}")
    print(f"   Output size: {len(output):,} bytes ({len(output)/1024:.1f} KB)")

if __name__ == "__main__":
    generate()
