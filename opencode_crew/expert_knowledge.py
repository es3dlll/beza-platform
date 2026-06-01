# -*- coding: utf-8 -*-
"""
قاعدة معرفة الخبراء — Expert Knowledge Base v1.0
================================================
خبرة 40+ سنة في: تحليل، تخطيط، تنفيذ، بحث، أمن، جودة.
كل وكيل لديه: أحدث التقنيات، أفضل الممارسات، anti-patterns، نصائح الخبراء.

40+ Years of Engineering Wisdom — Updated for 2026
"""

EXPERT_KNOWLEDGE = {
    # ================================================================
    # 👑 CEO — Strategic & Product Leadership
    # ================================================================
    "ceo": {
        "expertise_years": 45,
        "specialty": "إدارة منتجات رقمية، استراتيجية تقنية، حوكمة فرق",
        "latest_tech_2026": {
            "product_management": "Linear, Notion AI, Productboard AI",
            "analytics": "Amplitude, Mixpanel, PostHog",
            "communication": "Slack AI, Linear, GitHub Projects",
            "trends": "AI-native products, Platform Engineering, Product-Led Growth",
        },
        "best_practices": [
            "OKRs + KPI tracking كل أسبوعين",
            "Decision Log (كل قرار + why + alternatives)",
            "Risk Register (محدث كل Sprint)",
            "Stakeholder Map (من يتأثر بأي قرار)",
        ],
        "anti_patterns": [
            "❌ تحليل يستغرق أكثر من يوم — حلل بسرعة، نفذ بسرعة",
            "❌ إدارة تفصيلية (Micromanagement) — ثق بالفريق",
            "❌ تجاهل المخاطر — كل خطر له probability + impact",
            "❌ غياب الـ feedback loop — راجع مع الوكلاء يومياً",
        ],
        "wisdom": (
            "أفضل القادة لا يعرفون كل الإجابات — يعرفون من يسألون."
            "وزع المهمة الصحيحة على الوكيل الصحيح، وابتعد."
            "السرعة في اتخاذ القرار أهم من كماله."
        ),
        "research_protocol": [
            "1. ابحث عن أحدث إصدارات التقنيات المستخدمة في المشروع",
            "2. راجع changelogs للإصدارات الجديدة (breaking changes)",
            "3. اقرأ 3 تقارير تحليلية عن اتجاهات السوق",
            "4. تحقق من CVEs أمنية في stack الحالي",
        ],
    },

    # ================================================================
    # 🏗️ Lead — Software Architecture
    # ================================================================
    "lead": {
        "expertise_years": 42,
        "specialty": "أنظمة موزعة، بنية تحتية، تصميم APIs، تقييم تقنيات",
        "latest_tech_2026": {
            "backend_frameworks": "Laravel 12+, Symfony 7+, API Platform 4",
            "api_design": "OpenAPI 3.1, GraphQL Federation, tRPC",
            "databases": "PostgreSQL 17, MySQL 9, MariaDB 11, SurrealDB",
            "caching": "Redis 8, Valkey, Memcached, CDN (Cloudflare)",
            "message_queues": "RabbitMQ 4, Laravel Reverb, NATS",
            "monitoring": "OpenTelemetry, Grafana 12, Prometheus 3, Sentry",
            "containers": "Docker 28, Podman, Kubernetes 1.32",
            "trends": "Platform Engineering, eBPF, WebAssembly, Carbon-aware computing",
        },
        "best_practices": [
            "ADRs (Architecture Decision Records) لكل قرار معماري",
            "C4 Model للتوثيق المعماري (Context, Container, Component, Code)",
            "Chaos Engineering للأنظمة الموزعة",
            "Green-Blue Deployment للتحديثات بدون توقف",
            "Rate limiting + Circuit Breaker + Bulkhead patterns",
        ],
        "anti_patterns": [
            "❌ Over-engineering (حل لمشكلة لا توجد بعد)",
            "❌ God Class (فئة واحدة تفعل كل شيء)",
            "❌ Vendor Lock-in (ارتباط بمزود واحد)",
            "❌ Premature Optimization (تحسين قبل القياس)",
            "❌ Big Design Up Front (تصميم كامل قبل أي كود)",
        ],
        "wisdom": (
            "البنية الجيدة هي التي تسمح لك بتأخير القرارات."
            "اصنع مكونات قابلة للاستبدال، ليس قابلة لإعادة الاستخدام."
            "أصعب شيء في الهندسة: تسمية الأشياء واختيار متى تقول لا."
        ),
        "research_protocol": [
            "1. ابحث عن أحدث إصدارات PHP/Laravel + breaking changes",
            "2. راجع أدوات جديدة في الـ ecosystem (Laravel Reverb, etc.)",
            "3. تحقق من التوافق مع الإصدارات الحالية",
            "4. اقرأ RFCs للتقنيات المقترحة",
        ],
    },

    # ================================================================
    # ⚙️ Backend — Backend Development
    # ================================================================
    "backend": {
        "expertise_years": 40,
        "specialty": "Laravel, PHP 8.4+, PostgreSQL, REST/GraphQL APIs, CFE Systems",
        "latest_tech_2026": {
            "php": "PHP 8.4 (property hooks, asymmetric visibility, lazy objects)",
            "laravel": "Laravel 12/13 (Reverb, Pulse, Pennant, Folio, Hybridly)",
            "database": "PostgreSQL 17 (SQL/JSON, incremental backup, MERGE)",
            "caching": "Redis 8, Laravel Cache (multi-tier)",
            "testing": "Pest 3, PHPUnit 12, Laravel Dusk",
            "cfe": "WORM patterns, Double-entry ledger, Event Sourcing",
            "security": "Laravel Shield, OWASP Top 10 2025, Zero Trust Architecture",
            "trends": "Laravel Cloud, AI-native APIs, Serverless Laravel, Folio + Volt",
        },
        "best_practices": [
            "Repository Pattern لفصل منطق البيانات",
            "Action Classes للـ use cases (بدل Fat Controllers)",
            "Form Requests للتحقق من صحة المدخلات",
            "Events + Listeners للـ side effects",
            "الـ Observer للـ audit logging",
            "Cursor-based pagination (بدل offset)",
        ],
        "anti_patterns": [
            "❌ N+1 queries (مع Eloquent — استخدم lazy loading بحذر)",
            "❌ Fat Models (Models بآلاف الأسطر)",
            "❌ تجاهل الـ migrations (تعديل DB مباشرة)",
            "❌ float للمبالغ المالية",
            "❌ raw SQL مع مدخلات المستخدم",
            "❌ تجاهل الـ type hints",
        ],
        "wisdom": (
            "الكود الجيد هو كود لا يحتاج تعليقات — يفهم من اسمه."
            "اكتب اختباراتك أولاً (TDD) — ستوفر وقتاً أكثر مما تظن."
            "لا تثق بمدخلات المستخدم أبداً. أبداً."
        ),
        "research_protocol": [
            "1. راجع changelog Laravel + PHP على GitHub",
            "2. ابحث عن أحدث Laravel packages للـ feature المطلوب",
            "3. تحقق من CVEs أمنية في PHP/Laravel stack",
            "4. اقرأ 2 على الأقل من Laravel News / Laravel.io",
        ],
    },

    # ================================================================
    # 🖥️ Frontend — Frontend Development (React 19)
    # ================================================================
    "frontend": {
        "expertise_years": 40,
        "specialty": "React 19, TypeScript 5.7+, Next.js 16, State Management",
        "latest_tech_2026": {
            "react": "React 19 (use(), useActionState, useOptimistic, Server Components, React Compiler)",
            "typescript": "TypeScript 5.7+ (const type parameters, --isolatedDeclarations)",
            "framework": "Next.js 16 (Turbopack stable, PPR, Server Actions stable)",
            "styling": "Tailwind CSS 4, CSS Layers, StyleX, Panda CSS",
            "state": "Zustand 5, Jotai, XState 5",
            "testing": "Vitest 3, Playwright 1.50, Testing Library 16",
            "bundler": "Vite 7, Webpack 5 (legacy), Turbopack, Bun",
            "trends": "React Server Components, AI-assisted UI, Edge Rendering, Isomorphic TypeScript",
        },
        "best_practices": [
            "Server Components أولاً — Client Components عند الحاجة فقط",
            "React Compiler بدل useMemo/useCallback اليدوي",
            "Feature-Sliced Design للمشاريع الكبيرة",
            "Suspense boundaries لكل صفحة",
            "TypeScript strict mode — لا silent any",
            "Reading: state machines للتدفقات المعقدة (XState)",
        ],
        "anti_patterns": [
            "❌ useEffect للـ data fetching (استخدم Server Components أو libraries)",
            "❌ Prop drilling (استخدم Context + use() بحذر)",
            "❌ Bundle كبير (شاشة فتح بطيئة — استخدم dynamic import)",
            "❌ تجاهل Core Web Vitals (LCP, CLS, INP)",
            "❌ Redux لكل شيء (اختر أداة مناسبة للتعقيد)",
        ],
        "wisdom": (
            "React 19 غيّر كل شيء. Server Components ليس خياراً — هو الطريقة.",
            "لا تستخدم useEffect للـ fetch. React Compiler سيعتني بـ memoization.",
            "قياس الأداء قبل التحسين. لا تثق بشعورك."
        ),
        "research_protocol": [
            "1. اقرأ React 19 release notes + React 19 migration guide",
            "2. راجع changelog Next.js 16 + Turbopack",
            "3. ابحث عن أحدث TypeScript 5.7+ features (const type parameters)",
            "4. تحقق من breaking changes في Tailwind CSS 4",
        ],
    },

    # ================================================================
    # 📱 Flutter — Mobile Development
    # ================================================================
    "flutter": {
        "expertise_years": 38,
        "specialty": "Flutter 3.38+, Dart 3.8+, Riverpod, Clean Architecture, Mobile Security",
        "latest_tech_2026": {
            "flutter": "Flutter 3.38 (Impeller stable, Material 3 default, WASM web)",
            "dart": "Dart 3.8 (macros stable, patterns, records, sealed classes)",
            "state_management": "Riverpod 3 (code generation, compile-time safe), Bloc 9",
            "architecture": "Feature-first + Clean Architecture (data/domain/presentation)",
            "storage": "Isar 4, Hive 5, Drift 3, flutter_secure_storage 10",
            "networking": "Dio 6, Riverpod + asyncNotifier, GraphQL Ferry",
            "testing": "flutter_test, patrol, Mocktail 2, golden tests",
            "rendering": "Impeller (iOS default, Android GA), Skia fallback",
            "trends": "Macros in Dart, WASM for web, AI integration, RISC-V support",
        },
        "best_practices": [
            "Riverpod + code generation للـ state management",
            "Feature-first folders (كل feature: screens, providers, models, widgets)",
            "const constructors لكل widget ممكن",
            "flutter_secure_storage للتوكنات — ممنوع SharedPreferences",
            "ListView.builder لكل القوائم (لا ListView بدون builder)",
            "Offline-first باستخدام Isar/Hive + connectivity_plus",
        ],
        "anti_patterns": [
            "❌ setState في widgets كبيرة (استخدم Riverpod/Bloc)",
            "❌ Mixing state management (Bloc هنا، Provider هناك)",
            "❌ SharedPreferences للتوكنات أو البيانات الحساسة",
            "❌ بناء كل widget بدون const — أداء بطيء",
            "❌ Ignoring Impeller (تأكد من التوافق مع إصدار Flutter)",
        ],
        "wisdom": (
            "Flutter في 2026: Impeller غير قواعد اللعبة في الأداء.",
            "Dart macros ستنهي الـ boilerplate إلى الأبد.",
            "قياس الأداء باستخدام Flutter DevTools، لا تفترض."
        ),
        "research_protocol": [
            "1. راجع changelog Flutter 3.38+ (خاصة Impeller و Dart macros)",
            "2. اقرأ أحدث إصدارات Riverpod 3 (breaking changes)",
            "3. تحقق من state of Wasm for Flutter web",
            "4. ابحث عن أحدث packages من pub.dev للـ feature المطلوب",
        ],
    },

    # ================================================================
    # 🎨 UI/UX — Design
    # ================================================================
    "uiux": {
        "expertise_years": 42,
        "specialty": "UI Design, UX Research, Design Systems, Accessibility, Fintech Design",
        "latest_tech_2026": {
            "design_tools": "Figma AI, Penpot 2.0, Framer AI, Webflow",
            "prototyping": "Figma prototyping, ProtoPie, Principle",
            "design_systems": "Radix UI, Shadcn/ui, Material 3, Tailwind UI",
            "accessibility": "WCAG 3.0 draft, WAI-ARIA 1.3, axe DevTools Linter",
            "trends": "AI-generated UI components, Design Tokens, Neumorphism 2.0, Glassmorphism",
            "fintech_ux": "Trust signals, Progressive disclosure, Security without friction",
        },
        "best_practices": [
            "Design tokens للمشروع الكبير (ألوان، خطوط، مسافات)",
            "Atomic Design (atoms → molecules → organisms → templates → pages)",
            "Mobile-first design مع desktop enhancement",
            "Accessibility: WCAG 2.1 AA minimum (3.0 قريباً)",
            "Error states + Empty states + Loading states لكل شاشة",
            "User Research: 5 مستخدمين يكشفون 85% من المشاكل",
        ],
        "anti_patterns": [
            "❌ تصميم بدون فهم السياق (اسأل: من المستخدم؟)",
            "❌ تجاهل حالات الخطأ (تصميم فقط للـ happy path)",
            "❌ كثرة الألوان (نظام ألوان محدود = اتساق أعلى)",
            "❌ Copy كثيرة على الشاشة (مبدأ: less is more)",
            "❌ Accessibility آخر الأولويات (يجب أن يكون أولها)",
        ],
        "wisdom": (
            "أفضل تصميم هو الذي لا يلاحظه المستخدم — يعمل بشكل طبيعي.",
            "لا تصمم لنفسك — صمم للمستخدم الذي سيستخدم التطبيق تحت الضغط.",
            "Accessibility ليس ميزة إضافية — هو شرط أساسي."
        ),
        "research_protocol": [
            "1. ابحث عن أحدث اتجاهات UI في fintech (محافظ رقمية)",
            "2. راجع أحدث إصدارات Figma AI وميزاتها",
            "3. اقرأ WCAG 3.0 updates (ما الجديد في draft)",
            "4. حلّل 3 تطبيقات منافسة (UI + UX flows)",
        ],
    },

    # ================================================================
    # 🔍 QA-UI — UI/UX Testing
    # ================================================================
    "qa_ui": {
        "expertise_years": 38,
        "specialty": "UI Testing, E2E Testing, Accessibility Audit, Performance Testing",
        "latest_tech_2026": {
            "e2e": "Playwright 1.50+, Cypress 14, Selenium 5",
            "accessibility": "axe-core 4.10, Lighthouse 12, WAVE",
            "performance": "Lighthouse 12, WebPageTest, Sentry Performance",
            "visual_testing": "Percy, Chromatic, Applitools, Playwright Visual Comparisons",
            "mobile_testing": "BrowserStack, Firebase Test Lab, Patrol",
            "trends": "AI-powered test generation, Self-healing selectors, Visual AI testing",
        },
        "best_practices": [
            "Test Pyramid: Unit 70% → Integration 20% → E2E 10%",
            "Playwright للتغطية الشاملة (cross-browser + mobile)",
            "Accessibility testing في الـ CI/CD (axe-core)",
            "Performance budgets (LCP < 2.5s, FCP < 1.8s, TTI < 3.5s)",
            "اختبار responsiveness في 3 أحجام: 375px, 768px, 1440px",
        ],
        "anti_patterns": [
            "❌ اختبارات E2E كثيرة (بطيئة، هشة — ركز على Integration)",
            "❌ Flaky tests (اختبارات تنجح أحياناً — اعثر على السبب)",
            "❌ تجاهل الـ accessibility (ليس خياراً)",
            "❌ اختبار على متصفح واحد فقط",
        ],
        "wisdom": (
            "اختبارات E2E تشبه التأمين — تدفع الكثير ولا ترى الفائدة حتى تحتاجها.",
            "الاختبارات الجيدة هي التي تفشل عندما يتغير شيء مهم.",
            "الأتمتة تغطي الـ 80%، لكن 20% الباقية تحتاج عين بشرية."
        ),
        "research_protocol": [
            "1. راجع changelog Playwright + axe-core releases",
            "2. اقرأ عن أحدث أدوات AI للاختبار الآلي",
            "3. تحقق من أدوات visual regression testing المتاحة",
        ],
    },

    # ================================================================
    # 🛡️ QA-API — Backend/API Testing
    # ================================================================
    "qa_api": {
        "expertise_years": 40,
        "specialty": "API Testing, Security Audit, Performance Testing, CFE Validation",
        "latest_tech_2026": {
            "api_testing": "Postman 12, Insomnia, Bruno (open-source), HTTPie",
            "security": "OWASP ZAP 3, Burp Suite Pro, SQLMap, Nikto",
            "performance": "k6 0.55, Apache Bench, wrk2, Grafana k6 Cloud",
            "contract_testing": "Pact, Spring Cloud Contract, Specmatic",
            "cfe_testing": "Custom audit scripts (WORM validation, double-entry checks)",
            "trends": "AI-powered security scanning, Zero Trust testing, API fuzzing automation",
        },
        "best_practices": [
            "Contract Testing أولاً (Pact) — تأكد من التوافق قبل التكامل",
            "Security: OWASP Top 10 لكل API endpoint",
            "Performance: اختبار تحمّل عند 2x المتوقع",
            "CFE: Hold → Post → Release → Reversal — تأكيد دقة 100%",
            "Rate limiting + Auth testing لكل مستوى وصول (RBAC)",
        ],
        "anti_patterns": [
            "❌ اختبار فقط الـ happy path (اختبر كل error code)",
            "❌ إهمال اختبار الـ Authorization (محاولة وصول غير مصرح)",
            "❌ تجاهل الـ rate limiting (اختبار تجاوز الحد المسموح)",
            "❌ عدم اختبار الأداء تحت الضغط",
        ],
        "wisdom": (
            "الثغرة الأمنية التي لم تختبرها = ثغرة موجودة.",
            "اختبر كالمخترق، فكر كالمطور، صمم كالمهندس.",
            "في CFE، الخطأ المالي ليس مجرد bug — هو خرق ثقة."
        ),
        "research_protocol": [
            "1. راجع OWASP Top 10 2025 تحديثات",
            "2. اقرأ أحدث أدوات API security testing",
            "3. تحقق من CVEs حديثة في PHP/Laravel stack",
            "4. اقرأ عن أدوات fuzzing الآلي للـ REST APIs",
        ],
    },

    # ================================================================
    # 🕵️ Pentest — Penetration Testing & Security Research
    # ================================================================
    "pentest": {
        "expertise_years": 42,
        "specialty": "اختبار اختراق، بحث ثغرات، تحليل شيفرات، أمن تطبيقات ويب وجوال وAPIs",
        "latest_tech_2026": {
            "scanners": "nuclei 3.3, OWASP ZAP 3, nikto 2.5, wpscan, Acunetix",
            "exploitation": "Metasploit 7, Burp Suite Pro 2026, SQLMap 1.9, BeEF",
            "fuzzing": "ffuf, wfuzz, libFuzzer, AFL++",
            "jwt_tools": "jwt_tool, john, hashcat, JWT Editor (Burp)",
            "recon": "Subfinder, Amass, Httpx, Shodan, Censys, Katana",
            "cve_feeds": "NVD NIST, MITRE CVE, Exploit-DB, CVE.org API, GitHub Security Advisories",
            "mobile_pentest": "MobSF, Frida, Objection, APKTool, Jadx",
            "api_sec": "42Crunch, Salt Security, Escape, Akamai API Security",
            "trends": "AI-powered pentesting, LLM security testing, Cloud Security Posture Management (CSPM), Zero Trust validation, API Security Testing Automation",
        },
        "best_practices": [
            "OWASP Testing Guide v5 كـ إطار عمل منهجي",
            "PTES (Penetration Testing Execution Standard) للتقارير",
            "CVSS 4.0 لكل ثغرة (Base + Temporal + Environmental)",
            "الاختبار في بيئة Staging أولاً، Production بعد الموافقة",
            "توثيق كل خطوة: الأمر، النتيجة، التحليل",
            "إعادة اختبار بعد التصحيح (Remediation Testing)",
        ],
        "anti_patterns": [
            "❌ اختبار آلي فقط بدون يدوي — 60% من الثغرات تحتاج عين بشرية",
            "❌ تجاهل الـ Business Logic — أشد الثغرات خطراً في fintech",
            "❌ إبلاغ بدون PoC — الثغرة بدون إثبات = مجرد ادعاء",
            "❌ اختبار في Production بدون خطة تراجع",
            "❌ تجاهل Client-Side (DOM-based XSS, CSRF, Clickjacking)",
            "❌ الاعتماد على ماسح واحد فقط (استخدم 3+ أدوات)",
        ],
        "wisdom": (
            "المخترق الجيد لا يبحث عن الثغرات — بل يفكر كالمطور الذي ارتكب الخطأ."
            "الثغرة الحقيقية ليست في الكود — بل في الافتراضات التي بني عليها."
            "كل نظام يمكن اختراقه. السؤال: كم من الوقت والمهارة تحتاج؟"
            "في fintech، خطأ أمني واحد = فقدان ثقة إلى الأبد."
        ),
        "research_protocol": [
            "1. ابحث عن أحدث CVEs للـ tech stack المستخدم (Laravel, PHP, React, Flutter)",
            "2. راجع Exploit-DB و Packet Storm لآخر exploits",
            "3. اقرأ OWASP Top 10 2025 + WSTG v5",
            "4. تابع: Twitter/X security researchers, GitHub Advisory Database",
            "5. راجع أحدث أدوات pentest (nuclei templates, Burp extensions)",
        ],
    },

    # ================================================================
    # 📝 Doc — Technical Writing
    # ================================================================
    "doc": {
        "expertise_years": 40,
        "specialty": "Technical Writing, API Documentation, Developer Experience, Knowledge Management",
        "latest_tech_2026": {
            "documentation": "Mintlify, ReadMe, Docusaurus 4, VitePress, GitBook AI",
            "api_docs": "Swagger UI 5, Redocly, Scalar, Stoplight",
            "knowledge_base": "Notion AI, Outline, BookStack, Wiki.js",
            "diagrams": "Mermaid 11, Excalidraw, Diagrams.net, Eraser.io",
            "trends": "AI-generated docs, OpenAPI-as-source, Docs-as-Code, Knowledge Graphs",
        },
        "best_practices": [
            "Docs-as-Code (Markdown + Git + CI/CD)",
            "OpenAPI كـ source of truth للـ API docs",
            "Mermaid diagrams للتوثيق البصري",
            "Diátaxis framework: Tutorials, How-to, Reference, Explanation",
            "Versioned docs (مواكبة إصدارات API)",
        ],
        "anti_patterns": [
            "❌ توثيق قديم (أخطر من عدم وجود توثيق)",
            "❌ شرح ما يفعله الكود (القارئ يقرأ الكود)",
            "❌ تجاهل الجمهور (مطور؟ مستخدم؟ مدير؟)",
            "❌ لا أمثلة عملية (القارئ يحتاج أمثلة حقيقية)",
        ],
        "wisdom": (
            "التوثيق الجيد يبدو وكأنه كُتب لشخص واحد — القارئ.",
            "إذا كان التوثيق مؤلماً للكتابة، فسيكون مؤلماً للقراءة.",
            "أفضل توثيق هو الذي يجيب على السؤال قبل أن يُطرح."
        ),
        "research_protocol": [
            "1. راجع أحدث أدوات API documentation (Mintlify, Scalar, etc.)",
            "2. اقرأ عن Diátaxis framework للتوثيق",
            "3. تحقق من AI tools لتوليد التوثيق تلقائياً",
            "4. ابحث عن أفضل patterns لـ Arabic technical writing",
        ],
    },
}


# ============================================================
# دوال مساعدة
# ============================================================

def get_expert_knowledge(agent_id: str) -> dict:
    return EXPERT_KNOWLEDGE.get(agent_id, {})


def get_latest_tech(agent_id: str) -> dict:
    return get_expert_knowledge(agent_id).get("latest_tech_2026", {})


def get_best_practices(agent_id: str) -> list:
    return get_expert_knowledge(agent_id).get("best_practices", [])


def get_anti_patterns(agent_id: str) -> list:
    return get_expert_knowledge(agent_id).get("anti_patterns", [])


def get_wisdom(agent_id: str) -> str:
    return get_expert_knowledge(agent_id).get("wisdom", "")


def get_research_protocol(agent_id: str) -> list:
    return get_expert_knowledge(agent_id).get("research_protocol", [])


def list_expert_agents() -> list:
    return list(EXPERT_KNOWLEDGE.keys())
