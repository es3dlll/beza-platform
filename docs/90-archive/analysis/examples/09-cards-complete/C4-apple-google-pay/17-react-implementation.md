# 17 - تطبيق React (React Implementation) - Apple Pay و Google Pay

## هوك مخصص (Custom Hook): useWalletPay

```javascript
import { useState } from "react";
import api from "../services/api";

export function useWalletPay() {
  const [state, setState] = useState({ loading: false });

  const provisionCard = async (cardId, deviceId) => {
    setState({ loading: true });
    try {
      const res = await api.post("/api/v1/cards/wallet-token", { card_id: cardId, device_id: deviceId });
      setState({ loading: false, token: res.data.data });
    } catch (e) {
      setState({ loading: false, error: e.message });
    }
  };

  return { ...state, provisionCard };
}
```

## Wallet Token Component

```jsx
function WalletPayButton({ cardId, deviceId }) {
  const { loading, token, error, provisionCard } = useWalletPay();

  return (
    <div className="wallet-pay">
      <button onClick={() => provisionCard(cardId, deviceId)} disabled={loading}>
        {loading ? "Adding to Wallet..." : "Add to Apple/Google Pay"}
      </button>
      {token && <div className="success">Card added to wallet</div>}
      {error && <div className="error">{error}</div>}
    </div>
  );
}
```

## API Reference

| Endpoint | Method | Description |
|----------|--------|-------------|
| /api/v1/cards/wallet-token | POST | Provision card to wallet |
