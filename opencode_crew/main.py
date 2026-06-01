#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
OpenCode AI Crew — OpenCode هو المحرك
========================================
لست بحاجة لمفاتيح API خارجية. أنا (OpenCode) أ扮演 دور كل وكيل
باستخدام أدواتي المدمجة (Read, Write, Bash, Grep, ...).

الاستخدام:
  python main.py --new "وصف المهمة"              # جلسة جديدة
  python main.py --session SESSION_ID            # استعراض جلسة
  python main.py --list-sessions                  # عرض الجلسات
  python main.py --agents                         # عرض الوكلاء
"""

import argparse
import io
import sys
from pathlib import Path

if sys.stdout.encoding and sys.stdout.encoding.upper() != "UTF-8":
    try:
        sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding="utf-8", errors="replace")
        sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding="utf-8", errors="replace")
    except Exception:
        pass

from session_manager import SessionManager
from agent_profiles import list_agents, get_profile

VERSION = "2.0.0"


def main():
    parser = argparse.ArgumentParser(
        description="OpenCode AI Crew — OpenCode هو المحرك",
        formatter_class=argparse.RawDescriptionHelpFormatter,
        epilog=(
            "أمثلة:\n"
            "  python main.py --new \"بناء نظام تسجيل دخول\"\n"
            "  python main.py --session SESSION_ID\n"
            "  python main.py --list-sessions\n"
            "  python main.py --agents\n"
        ),
    )

    parser.add_argument("--new", type=str, help="إنشاء جلسة جديدة لوصف المهمة")
    parser.add_argument("--session", type=str, help="عرض تفاصيل جلسة سابقة")
    parser.add_argument("--list-sessions", action="store_true", help="عرض كل الجلسات")
    parser.add_argument("--agents", action="store_true", help="عرض قائمة الوكلاء")
    parser.add_argument("--version", action="store_true", help="عرض الإصدار")

    args = parser.parse_args()

    if args.version:
        print(f"OpenCode AI Crew v{VERSION}")
        return

    if args.agents:
        print(f"\nفريق العمل — {len(list_agents())} وكلاء:\n")
        print(f"{'':<4} {'الوكيل':<12} {'الدور':<45} {'Repo'}")
        print("    " + "-" * 100)
        for a in list_agents():
            emoji = a.get('emoji', '  ')
            repo = a.get('repo', '') or '(main)'
            print(f"  {emoji} {a['id']:<10} {a['role']:<45} {repo}")
        print("\nهرم التواصل: CEO → Lead → [Backend, Frontend, Flutter, UI/UX] ← Doc")
        return

    mgr = SessionManager()

    if args.list_sessions:
        sessions = mgr.list_sessions()
        if not sessions:
            print("لا توجد جلسات سابقة.")
            return
        print(f"\nالجلسات ({len(sessions)}):\n")
        print(f"{'معرف الجلسة':<35} {'المشروع':<15} {'الحالة':<10} {'التاريخ':<20}")
        print("-" * 80)
        for s in sessions:
            print(
                f"{s['session_id']:<35} "
                f"{s.get('project', 'N/A'):<15} "
                f"{s.get('status', 'N/A'):<10} "
                f"{s.get('created_at', 'N/A'):<20}"
            )
        return

    if args.session:
        s = mgr.load_session(args.session)
        if not s:
            print(f"الجلسة {args.session} غير موجودة.")
            return
        print(f"\nجلسة: {s['session_id']}")
        print(f"المشروع: {s.get('project', 'N/A')}")
        print(f"الحالة: {s.get('status', 'N/A')}")
        print(f"المهام المنجزة: {len(s.get('task_outputs', {}))}")
        print(f"عدد القطع الأثرية: {len(s.get('artifacts', []))}")
        if s.get("artifacts"):
            print("\nملفات مولدة:")
            for a in s["artifacts"]:
                print(f"  {a.get('agent', '?'):<10} {a.get('file', '?')}")
        return

    if args.new:
        s = mgr.create_session(args.new[:40])
        print(f"\nجلسة جديدة: {s['session_id']}")
        print(f"الطلب: {args.new}")
        print(f"\n===")
        print(f"الآن أنا (OpenCode) سأبدأ كـ CEO لتحليل الطلب.")
        print(f"استخدم workflow = create_workflow(args.new) للمتابعة.")
        print(f"===")
        return

    parser.print_help()


if __name__ == "__main__":
    main()
