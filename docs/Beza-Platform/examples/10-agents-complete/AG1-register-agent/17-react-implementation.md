# 17 - تطبيق React (React Implementation) - تسجيل وكيل جديد (Register Agent)

## هوك مخصص (Custom Hook): useRegisterAgent

```javascript
import { useState } from "react";
import api from "../services/api";

export function useRegisterAgent() {
  const [state, setState] = useState({ loading: false });

  const submit = async (formData) => {
    setState({ loading: true });
    try {
      const res = await api.post("/api/v1/agent/register", formData, {
        headers: { "Content-Type": "multipart/form-data" }
      });
      setState({ loading: false, data: res.data.data });
    } catch (e) {
      setState({ loading: false, error: e.response?.data?.message || e.message });
    }
  };

  return { ...state, submit };
}
```

## التسجيل (Registration) Form Component

```jsx
function AgentRegisterForm() {
  const { loading, data, error, submit } = useRegisterAgent();
  const [form, setForm] = useState({ phone: "", documents: null });

  const handleSubmit = (e) => {
    e.preventDefault();
    const fd = new FormData();
    fd.append("phone", form.phone);
    if (form.documents) fd.append("documents", form.documents);
    submit(fd);
  };

  return (
    <form onSubmit={handleSubmit}>
      <input placeholder="Phone" value={form.phone} onChange={e => setForm({...form, phone: e.target.value})} />
      <input type="file" onChange={e => setForm({...form, documents: e.target.files[0]})} />
      <button type="submit" disabled={loading}>
        {loading ? "Submitting..." : "Register as Agent"}
      </button>
      {error && <div className="error">{error}</div>}
    </form>
  );
}
```

## API Reference

| Endpoint | Method | Description |
|----------|--------|-------------|
| /api/v1/agent/register | POST | Submit agent registration |
