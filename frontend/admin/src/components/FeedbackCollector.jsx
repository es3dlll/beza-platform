import React, { useState, useCallback } from 'react';
import './FeedbackCollector.css';

const CATEGORIES = [
  { value: 'technical_issue', label: 'مشكلة تقنية' },
  { value: 'feature_request', label: 'اقتراح ميزة' },
  { value: 'general_question', label: 'سؤال عام' },
  { value: 'security_report', label: 'تقرير أمان' },
];

export default function FeedbackCollector() {
  const [isOpen, setIsOpen] = useState(false);
  const [category, setCategory] = useState('');
  const [description, setDescription] = useState('');
  const [rating, setRating] = useState(0);
  const [screenshot, setScreenshot] = useState(null);
  const [allowFollowup, setAllowFollowup] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [ticketId, setTicketId] = useState(null);
  const [error, setError] = useState(null);

  const reset = useCallback(() => {
    setCategory('');
    setDescription('');
    setRating(0);
    setScreenshot(null);
    setAllowFollowup(false);
    setTicketId(null);
    setError(null);
  }, []);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!description.trim()) return;

    setSubmitting(true);
    setError(null);

    const formData = new FormData();
    formData.append('category', category || 'general_question');
    formData.append('description', description);
    formData.append('rating', String(rating || 3));
    formData.append('allow_followup', String(allowFollowup));
    if (screenshot) formData.append('screenshot', screenshot);

    try {
      const res = await fetch('/api/v1/feedback/beta', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData,
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.message || 'فشل الإرسال');
      setTicketId(data.ticket_id);
    } catch (err) {
      setError(err.message);
    } finally {
      setSubmitting(false);
    }
  };

  if (ticketId) {
    return (
      <div className="fc-overlay">
        <div className="fc-card fc-thanks">
          <button className="fc-close" onClick={() => { reset(); setIsOpen(false); }}>&times;</button>
          <div className="fc-thanks-icon">&#10003;</div>
          <h3>شكراً لملاحظاتك!</h3>
          <p>رقم التذكرة: <strong>{ticketId}</strong></p>
          <p>سنراجع ملاحظاتك ونتواصل معك قريباً.</p>
          <button className="fc-btn" onClick={() => { reset(); setIsOpen(false); }}>حسناً</button>
        </div>
      </div>
    );
  }

  return (
    <>
      <button className="fc-fab" onClick={() => setIsOpen(!isOpen)} title="إرسال ملاحظات">
        &#9998;
      </button>

      {isOpen && (
        <div className="fc-overlay">
          <form className="fc-card" onSubmit={handleSubmit}>
            <button type="button" className="fc-close" onClick={() => setIsOpen(false)}>&times;</button>
            <h3>شاركنا ملاحظاتك</h3>

            <label>التصنيف</label>
            <select value={category} onChange={(e) => setCategory(e.target.value)}>
              <option value="">اختر التصنيف...</option>
              {CATEGORIES.map((c) => (
                <option key={c.value} value={c.value}>{c.label}</option>
              ))}
            </select>

            <label>الوصف</label>
            <textarea
              value={description}
              onChange={(e) => setDescription(e.target.value.slice(0, 500))}
              placeholder="صِف ملاحظاتك بالتفصيل..."
              rows={4}
              maxLength={500}
            />
            <small>{description.length}/500</small>

            <label>لقطة شاشة (اختياري)</label>
            <input type="file" accept="image/*" onChange={(e) => setScreenshot(e.target.files[0])} />

            <label>تقييم التجربة</label>
            <div className="fc-stars">
              {[1, 2, 3, 4, 5].map((n) => (
                <span key={n} className={n <= rating ? 'fc-star-active' : ''} onClick={() => setRating(n)}>
                  &#9733;
                </span>
              ))}
            </div>

            <label className="fc-checkbox">
              <input type="checkbox" checked={allowFollowup} onChange={(e) => setAllowFollowup(e.target.checked)} />
              أوافق على التواصل معي لتوضيح الملاحظة
            </label>

            {error && <div className="fc-error">{error}</div>}

            <button type="submit" className="fc-btn" disabled={submitting || !description.trim()}>
              {submitting ? 'جاري الإرسال...' : 'إرسال الملاحظة'}
            </button>
          </form>
        </div>
      )}
    </>
  );
}
