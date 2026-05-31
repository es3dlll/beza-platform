<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة الملاحظات — بيزا</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['system-ui', 'sans-serif'] }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 p-6">
    <div class="max-w-7xl mx-auto" id="app">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">لوحة الملاحظات — الفترة التجريبية</h1>
            <button onclick="exportCSV()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                تصدير CSV
            </button>
        </div>

        <!-- Filters -->
        <div class="flex gap-4 mb-6 flex-wrap">
            <select id="filterCategory" onchange="loadFeedback()" class="border rounded-lg px-3 py-2">
                <option value="">جميع التصنيفات</option>
                <option value="technical_issue">مشكلة تقنية</option>
                <option value="feature_request">اقتراح ميزة</option>
                <option value="general_question">سؤال عام</option>
                <option value="security_report">تقرير أمان</option>
            </select>
            <select id="filterStatus" onchange="loadFeedback()" class="border rounded-lg px-3 py-2">
                <option value="">جميع الحالات</option>
                <option value="new">جديد</option>
                <option value="in_review">قيد المراجعة</option>
                <option value="resolved">تم الحل</option>
                <option value="rejected">مرفوض</option>
            </select>
            <input id="filterDate" type="date" onchange="loadFeedback()" class="border rounded-lg px-3 py-2">
            <button onclick="loadFeedback()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                بحث
            </button>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-5 gap-4 mb-6" id="summaryCards">
            <div class="bg-white rounded-xl p-4 shadow-sm"><div class="text-2xl font-bold" id="totalCount">0</div><div class="text-gray-500 text-sm">الكل</div></div>
            <div class="bg-white rounded-xl p-4 shadow-sm"><div class="text-2xl font-bold text-red-600" id="criticalCount">0</div><div class="text-gray-500 text-sm">حرج</div></div>
            <div class="bg-white rounded-xl p-4 shadow-sm"><div class="text-2xl font-bold text-yellow-600" id="pendingCount">0</div><div class="text-gray-500 text-sm">قيد المراجعة</div></div>
            <div class="bg-white rounded-xl p-4 shadow-sm"><div class="text-2xl font-bold text-green-600" id="resolvedCount">0</div><div class="text-gray-500 text-sm">تم الحل</div></div>
            <div class="bg-white rounded-xl p-4 shadow-sm"><div class="text-2xl font-bold text-blue-600" id="avgRating">0</div><div class="text-gray-500 text-sm">متوسط التقييم</div></div>
        </div>

        <!-- Feedback List -->
        <div class="bg-white rounded-xl shadow-sm" id="feedbackList">
            <div class="p-6 text-center text-gray-400">جاري التحميل...</div>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center gap-2 mt-6" id="pagination"></div>
    </div>

    <!-- Detail Modal -->
    <div id="detailModal" class="hidden fixed inset-0 bg-black/40 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 max-w-lg w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-start mb-4">
                <h2 class="text-xl font-bold" id="modalTitle">تفاصيل الملاحظة</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
            </div>
            <div id="modalBody"></div>
        </div>
    </div>

    <script>
        let currentPage = 1;

        async function loadFeedback(page = 1) {
            currentPage = page;
            const params = new URLSearchParams({
                page,
                per_page: 20,
                category: document.getElementById('filterCategory').value,
                status: document.getElementById('filterStatus').value,
            });
            if (document.getElementById('filterDate').value) {
                params.set('date', document.getElementById('filterDate').value);
            }

            try {
                const res = await fetch(`/api/v1/admin/beta/feedback?${params}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                renderFeedback(data.data || []);
                renderPagination(data);
                renderSummary(data.data || []);
            } catch (e) {
                document.getElementById('feedbackList').innerHTML =
                    '<div class="p-6 text-center text-red-500">فشل تحميل البيانات</div>';
            }
        }

        function renderFeedback(items) {
            const container = document.getElementById('feedbackList');
            if (!items.length) {
                container.innerHTML = '<div class="p-6 text-center text-gray-400">لا توجد ملاحظات</div>';
                return;
            }

            const statusBadge = (s) => {
                const map = { new: 'bg-yellow-100 text-yellow-800', in_review: 'bg-blue-100 text-blue-800', resolved: 'bg-green-100 text-green-800', rejected: 'bg-gray-100 text-gray-800' };
                return `<span class="px-2 py-1 rounded-full text-xs ${map[s] || 'bg-gray-100'}">${s}</span>`;
            };

            const categoryLabel = (c) => {
                const map = { technical_issue: 'مشكلة تقنية', feature_request: 'اقتراح ميزة', general_question: 'سؤال عام', security_report: '⚠️ أمان' };
                return map[c] || c;
            };

            container.innerHTML = items.map(item => `
                <div class="border-b border-gray-100 p-4 hover:bg-gray-50 cursor-pointer"
                     onclick='openModal(${JSON.stringify(item).replace(/'/g, "&#39;")})'>
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="font-medium">${categoryLabel(item.category)}</span>
                            <span class="mx-2 text-gray-300">|</span>
                            <span class="text-yellow-500">${'★'.repeat(item.rating)}${'☆'.repeat(5 - item.rating)}</span>
                            ${statusBadge(item.status)}
                        </div>
                        <span class="text-gray-400 text-sm">${new Date(item.created_at).toLocaleDateString('ar-SA')}</span>
                    </div>
                    <p class="mt-2 text-gray-700 line-clamp-2">${item.description}</p>
                </div>
            `).join('');
        }

        function renderPagination(data) {
            const container = document.getElementById('pagination');
            if (data.last_page <= 1) { container.innerHTML = ''; return; }
            let html = '';
            for (let i = 1; i <= data.last_page; i++) {
                html += `<button onclick="loadFeedback(${i})"
                    class="px-3 py-1 rounded ${i === data.current_page ? 'bg-blue-600 text-white' : 'bg-white border hover:bg-gray-50'}">${i}</button>`;
            }
            container.innerHTML = html;
        }

        function renderSummary(items) {
            document.getElementById('totalCount').textContent = items.length;
            document.getElementById('criticalCount').textContent = items.filter(i => i.category === 'security_report').length;
            document.getElementById('pendingCount').textContent = items.filter(i => i.status === 'new' || i.status === 'in_review').length;
            document.getElementById('resolvedCount').textContent = items.filter(i => i.status === 'resolved').length;
            const avg = items.length ? (items.reduce((s, i) => s + i.rating, 0) / items.length).toFixed(1) : 0;
            document.getElementById('avgRating').textContent = avg;
        }

        function openModal(item) {
            document.getElementById('detailModal').classList.remove('hidden');
            document.getElementById('modalTitle').textContent = `ملاحظة #${item.id}`;
            document.getElementById('modalBody').innerHTML = `
                <div class="space-y-3">
                    <div><strong>المستخدم:</strong> ${item.user_id}</div>
                    <div><strong>التصنيف:</strong> ${item.category}</div>
                    <div><strong>التقييم:</strong> ${'★'.repeat(item.rating)}${'☆'.repeat(5 - item.rating)}</div>
                    <div><strong>الوصف:</strong><p class="mt-1 p-3 bg-gray-50 rounded">${item.description}</p></div>
                    ${item.screenshot_url ? `<div><strong>لقطة شاشة:</strong><br><img src="${item.screenshot_url}" class="mt-1 max-h-48 rounded"></div>` : ''}
                    ${item.analysis_metadata ? `<div><strong>التحليل التلقائي:</strong><pre class="mt-1 p-3 bg-gray-50 rounded text-sm">${JSON.stringify(item.analysis_metadata, null, 2)}</pre></div>` : ''}
                    <div><strong>الحالة:</strong>
                        <select onchange="updateStatus('${item.feedback_id}', this.value)" class="border rounded px-2 py-1 mr-2">
                            <option value="new" ${item.status === 'new' ? 'selected' : ''}>جديد</option>
                            <option value="in_review" ${item.status === 'in_review' ? 'selected' : ''}>قيد المراجعة</option>
                            <option value="resolved" ${item.status === 'resolved' ? 'selected' : ''}>تم الحل</option>
                            <option value="rejected" ${item.status === 'rejected' ? 'selected' : ''}>مرفوض</option>
                        </select>
                    </div>
                    <div><strong>ملاحظات داخلية:</strong>
                        <textarea id="internalNotes" class="w-full border rounded p-2 mt-1" rows="3">${item.internal_notes ? JSON.stringify(item.internal_notes) : ''}</textarea>
                        <button onclick="saveNotes('${item.feedback_id}')" class="mt-2 bg-gray-600 text-white px-3 py-1 rounded text-sm">حفظ</button>
                    </div>
                    ${item.allow_followup ? '<div class="p-3 bg-blue-50 text-blue-700 rounded">المستخدم وافق على التواصل للمتابعة</div>' : ''}
                </div>
            `;
        }

        function closeModal() {
            document.getElementById('detailModal').classList.add('hidden');
        }

        async function updateStatus(feedbackId, status) {
            await fetch(`/api/v1/admin/beta/feedback/${feedbackId}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ status }),
            });
            loadFeedback(currentPage);
        }

        async function saveNotes(feedbackId) {
            const notes = document.getElementById('internalNotes').value;
            await fetch(`/api/v1/admin/beta/feedback/${feedbackId}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ internal_notes: [notes] }),
            });
        }

        async function exportCSV() {
            try {
                const res = await fetch('/api/v1/admin/beta/feedback/export', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                const blob = new Blob([data.csv], { type: 'text/csv' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'beta-feedback-export.csv';
                a.click();
                URL.revokeObjectURL(url);
            } catch (e) {
                alert('فشل التصدير');
            }
        }

        loadFeedback();
    </script>
</body>
</html>
