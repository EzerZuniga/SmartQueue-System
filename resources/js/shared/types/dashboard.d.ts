export interface DemandData {
    date: string;
    tickets: number;
}

export interface DistributionData {
    name: string;
    count: number;
    created_at_formatted: string;
}

export interface OperatorData {
    id: number;
    operator_name: string;
    counter_name: string;
    status: string;
    current_ticket: string | null;
    call_started_at: string | null;
}

export interface DashboardStats {
    kpis: {
        waiting_count: number;
        avg_wait_time: string;
        active_counters: number;
        abandonment_rate: string;
    };
    charts: {
        demand: DemandData[];
        distribution: DistributionData[];
    };
    operators: OperatorData[];
    alerts: any[];
}
