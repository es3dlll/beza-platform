export interface Agent {
  id: number;
  user_id: number;
  business_name: string;
  license_number: string;
  license_expiry: string;
  commission_rate: number;
  daily_deposit_limit: number;
  daily_withdrawal_limit: number;
  balance: number;
  status: "pending" | "active" | "suspended" | "closed";
  region: string | null;
  created_at: string;
  user?: { name: string; email: string; phone: string };
}

export interface AgentCommission {
  id: number;
  agent_id: number;
  amount: number;
  currency: string;
  type: string;
  status: "accrued" | "settled" | "voided";
  created_at: string;
}

export interface AgentSettlement {
  id: number;
  agent_id: number;
  period_start: string;
  period_end: string;
  total_commission: number;
  net_amount: number;
  currency: string;
  status: string;
  processed_at: string;
}

export interface FraudAlert {
  id: number;
  agent_id: number;
  type: string;
  severity: "low" | "medium" | "high" | "critical";
  description: string;
  detected_at: string;
  status: "open" | "investigating" | "resolved";
}
