#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
OpenCode AI Crew v5.0
======================
OpenCode هو المحرك — 9 وكلاء × 6 مراحل إلزامية.
لا حاجة لمفاتيح API خارجية.

الاستخدام:
  python main.py --new "وصف المهمة"              # جلسة جديدة
  python main.py --session SESSION_ID            # استعراض جلسة
  python main.py --list-sessions                  # عرض الجلسات
  python main.py --agents                         # عرض الوكلاء والمراحل
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
from agent_profiles import list_agents, get_profile, VERIFICATION_STAGES, list_stages

VERSION = "5.0.0"


def print_pipeline_overview():
    """طباعة نظرة عامة على الـ 6 مراحل."""
    print("\nنظام الـ 6 مراحل — Verification Pipeline:")
    print("-" * 60)
    for sid in list_stages():
        info = VERIFICATION_STAGES[sid]
        print(f"  {info['icon']} {info['name']:<12} — {info['description']}")
    print()


def print_agent_with_stages(agent: dict):
    """طباعة وكيل واحد مع مراحله."""
    emoji = agent.get('emoji', '  ')
    profile = get_profile(agent["id"])
    pipeline = profile.get("pipeline", {}) if profile else {}

    print(f"\n  {emoji} {agent['id']:<10} {agent['role']}")
    repo = agent.get('repo', '') or '(main)'
    print(f"     Repo: {repo}")
    print(f"     {agent.get('description', '')}")

    if pipeline:
        for sid in list_stages():
            stage_info = VERIFICATION_STAGES.get(sid, {})
            stage_pipeline = pipeline.get(sid, {})
            if stage_pipeline:
                icon = stage_info.get("icon", "")
                name = stage_info.get("name", sid)
                actions = stage_pipeline.get("actions", [])
                criteria = stage_pipeline.get("success_criteria", [])
                conditions = stage_pipeline.get("conditions", [])

                print(f"     {icon} {name}:")
                for a in actions[:3]:
                    print(f"        • {a}")
                if criteria:
                    print(f"        ✓ نجاح: {' | '.join(criteria[:2])}")
                if conditions:
                    print(f"        ⚠ شرط: {' | '.join(conditions[:2])}")
    print()


def main():
    parser = argparse.ArgumentParser(
        description="OpenCode AI Crew v5 — 9 وكلاء × 6 مراحل",
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
    parser.add_argument("--agents", action="store_true", help="عرض قائمة الوكلاء مع المراحل")
    parser.add_argument("--version", action="store_true", help="عرض الإصدار")

    args = parser.parse_args()

    if args.version:
        print(f"OpenCode AI Crew v{VERSION}")
        return

    if args.agents:
        agents = list_agents()
        print(f"\nفريق العمل — {len(agents)} وكلاء × 6 مراحل لكل وكيل:\n")

        # طباعة الـ 6 مراحل أولاً
        print_pipeline_overview()

        # ثم كل وكيل مع مراحله
        for a in agents:
            print_agent_with_stages(a)

        print("هرم التواصل:")
        print("  👑 CEO → 🏗️ Lead → 🎨 UI/UX + ⚙️ Backend + 🖥️ Frontend + 📱 Flutter")
        print("         → 🔍 QA-UI + 🛡️ QA-API")
        print("         → 📝 Doc")
        print()
        print("التدفق: الفحص → اختبار أولي → فحص موسع → تطوير → اختبار نهائي → تأكيد")
        print("كل وكيل يمر بـ 6 مراحل إلزامية قبل التسليم.")
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
        completed = s.get('completed_tasks', [])
        print(f"الوكلاء المكتملون: {len(completed)}")
        print(f"عدد القطع الأثرية: {len(s.get('artifacts', []))}")
        if completed:
            print("\nالوكلاء المكتملون:")
            for aid in completed:
                profile = get_profile(aid)
                if profile:
                    print(f"  {profile.get('emoji', '')} {profile['role']}")
        if s.get("artifacts"):
            print("\nملفات مولدة:")
            for a in s["artifacts"]:
                stage = a.get('stage', '')
                agent = a.get('agent', '?')
                print(f"  {agent:<10}/{stage:<15} {a.get('file', '?')}")
        return

    if args.new:
        s = mgr.create_session(args.new[:40])
        print(f"\nجلسة جديدة: {s['session_id']}")
        print(f"الطلب: {args.new}")

        # عرض مسار العمل
        print(f"\n=== مسار العمل ===")
        print(f"سأبدأ كـ 👑 CEO — وسأمر أنا وكل وكيل بـ 6 مراحل إلزامية:")
        print(f"  🔎 فحص → 🧪 اختبار أولي → 🔬 فحص موسع → ⚒️ تطوير → ✅ اختبار نهائي → 🏁 تأكيد")
        print(f"\nاستخدم: workflow = create_workflow(args.new)")
        print(f"ثم: workflow.start_agent_stage('agent_id') لكل مرحلة")
        return

    parser.print_help()


if __name__ == "__main__":
    main()
