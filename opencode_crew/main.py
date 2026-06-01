#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
OpenCode AI Crew v6.0
======================
OpenCode هو المحرك — 9 وكلاء × 6 مراحل × تفكير خبير (40+ سنة).
كل وكيل يخطط كخبير، يبحث عن آخر التحديثات، ينفذ بدقة، يراجع بعمق.

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
from expert_knowledge import get_expert_knowledge, get_latest_tech, get_wisdom

VERSION = "6.0.0"


def print_pipeline_overview():
    """طباعة نظرة عامة على الـ 6 مراحل."""
    print("\nنظام الـ 6 مراحل — Verification Pipeline (بتفكير خبير):")
    print("-" * 65)
    for sid in list_stages():
        info = VERIFICATION_STAGES[sid]
        print(f"  {info['icon']} {info['name']:<12} — {info['description']}")
    print()


def print_expert_info(agent_id: str):
    """طباعة معلومات الخبير للوكيل."""
    expert = get_expert_knowledge(agent_id)
    if not expert:
        return
    print(f"     👴 خبرة: {expert.get('expertise_years', '40+')} سنة — {expert.get('specialty', '')}")

    tech = get_latest_tech(agent_id)
    if tech:
        print(f"     📡 آخر التقنيات 2026:")
        for k, v in list(tech.items())[:3]:
            print(f"        {k}: {v}")

    wisdom = get_wisdom(agent_id)
    if wisdom:
        print(f"     💡 حكمة: {wisdom[:100]}...")

    anti = expert.get("anti_patterns", [])
    if anti:
        print(f"     ⚠️ Anti-Patterns:")
        for a in anti[:2]:
            print(f"        {a}")


def print_agent_with_stages(agent: dict):
    """طباعة وكيل واحد مع مراحله وخبرته."""
    emoji = agent.get('emoji', '  ')
    profile = get_profile(agent["id"])
    pipeline = profile.get("pipeline", {}) if profile else {}

    print(f"\n  {emoji} {agent['id']:<10} {agent['role']}")
    repo = agent.get('repo', '') or '(main)'
    print(f"     Repo: {repo}")
    print(f"     {agent.get('description', '')}")

    # معلومات الخبير
    print_expert_info(agent["id"])

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
        print("         → 🔍 QA-UI + 🛡️ QA-API + 🕵️ Pentest")
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

        # عرض مسار العمل بتفكير خبير
        print(f"\n=== مسار العمل — بتفكير خبير (40+ سنة) ===")
        print(f"سأبدأ كـ 👑 CEO — أنا وكل وكيل نمر بـ 6 مراحل تفكير خبير:")
        print(f"  🔎 فحص (بتحليل عميق)")
        print(f"  🧪 اختبار أولي (مع بحث عن آخر التحديثات 2026)")
        print(f"  🔬 فحص موسع (مع مسح anti-patterns)")
        print(f"  ⚒️ تطوير (بأفضل الممارسات)")
        print(f"  ✅ اختبار نهائي (مع تحليل خبير)")
        print(f"  🏁 تأكيد (مع حكمة 40 سنة)")

        print(f"\nكل وكيل يبحث عن أحدث التقنيات قبل أن يبدأ.")
        print(f"كل وكيل يفحص anti-patterns قبل أن يكتب سطراً.")
        print(f"كل وكيل ينهي بـ commit + push في repo الخاص به.")

        print(f"\nاستخدم: workflow = create_workflow(args.new)")
        print(f"ثم: workflow.start_agent_stage('agent_id') لكل مرحلة")
        return

    parser.print_help()


if __name__ == "__main__":
    main()
