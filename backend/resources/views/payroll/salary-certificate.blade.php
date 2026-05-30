<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="utf-8">
    <title>شهادة راتب - {{ $certificate_no }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 14px; padding: 40px; color: #222; }
        .header { text-align: center; border-bottom: 3px solid #1a237e; padding-bottom: 20px; margin-bottom: 30px; }
        .header h1 { color: #1a237e; font-size: 24px; margin: 0 0 5px; }
        .header h2 { color: #666; font-size: 16px; margin: 0; font-weight: normal; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .info-table td { padding: 10px 15px; border: 1px solid #ddd; }
        .info-table td.label { background: #f5f5f5; font-weight: bold; width: 35%; }
        .salary-box { border: 2px solid #1a237e; border-radius: 8px; padding: 20px; text-align: center; margin: 30px 0; }
        .salary-box .amount { font-size: 28px; color: #1a237e; font-weight: bold; }
        .salary-box .words { font-size: 16px; color: #555; margin-top: 8px; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #eee; padding-top: 20px; }
        .stamp { text-align: left; margin-top: 30px; }
        .stamp .box { display: inline-block; border: 2px solid #1a237e; padding: 10px 30px; border-radius: 5px; color: #1a237e; font-weight: bold; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; }
        .badge-completed { background: #e8f5e9; color: #2e7d32; }
    </style>
</head>
<body>
    <div class="header">
        <h1>شهادة راتب</h1>
        <h2>Salary Certificate</h2>
        <p style="margin-top: 10px; font-size: 12px;">رقم الشهادة: {{ $certificate_no }} | تاريخ الإصدار: {{ $issue_date }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">اسم الشركة</td>
            <td>{{ $employer_ar }} ({{ $employer }})</td>
        </tr>
        <tr>
            <td class="label">اسم الموظف</td>
            <td>{{ $employee_name }}</td>
        </tr>
        <tr>
            <td class="label">رقم الهاتف</td>
            <td>{{ $employee_phone }}</td>
        </tr>
        @if($national_id !== '—')
        <tr>
            <td class="label">رقم الهوية</td>
            <td>{{ $national_id }}</td>
        </tr>
        @endif
        <tr>
            <td class="label">المسمى الوظيفي</td>
            <td>{{ $job_title }}</td>
        </tr>
        <tr>
            <td class="label">الفترة</td>
            <td>{{ $period }}</td>
        </tr>
        <tr>
            <td class="label">رقم الدفعة</td>
            <td>{{ $batch_reference }}</td>
        </tr>
        <tr>
            <td class="label">الحالة</td>
            <td><span class="badge badge-completed">{{ $status }}</span></td>
        </tr>
        <tr>
            <td class="label">تاريخ المعالجة</td>
            <td>{{ $processed_at }}</td>
        </tr>
    </table>

    <div class="salary-box">
        <div style="font-size: 14px; color: #666; margin-bottom: 5px;">صافي الراتب</div>
        <div class="amount">{{ $salary }} {{ $currency }}</div>
        <div class="words">{{ $salary_in_words }}</div>
    </div>

    <div class="stamp">
        <div class="box">ختم الشركة</div>
    </div>

    <div class="footer">
        <p>هذه الشهادة صادرة عن منصة بيزا وتعتبر وثيقة رسمية</p>
        <p>This certificate is issued by Beza Platform and is an official document</p>
        <p style="margin-top: 5px;">{{ $certificate_no }} | {{ $issue_date }}</p>
    </div>
</body>
</html>
