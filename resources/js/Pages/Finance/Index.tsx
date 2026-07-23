import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import AccountIcon from '@/Components/AccountIcon';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type Account = { id: number; name: string; type: string; opening_balance_amount: number; included_in_planning: boolean };
type CashFlow = { id: number; direction: 'income' | 'expense'; label: string; amount: number; due_on: string; is_essential: boolean };
type Position = { current_balance: number; spendable: number; daily_available: number; days_to_cover: number };
type BudgetCycle = { cycle_start_day: number; monthly_budget_amount: number; cycle_budget_renews_automatically: boolean };

const money = (value: number) => new Intl.NumberFormat('fr-FR').format(Math.max(value, 0)) + ' FCFA';
const fieldClass = 'mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500';

function AccountRow({ account, canDelete }: { account: Account; canDelete: boolean }) {
    const [editing, setEditing] = useState(false);
    const form = useForm({ name: account.name, type: account.type, opening_balance_amount: account.opening_balance_amount, included_in_planning: account.included_in_planning });

    const save = (event: FormEvent) => {
        event.preventDefault();
        form.put(route('finances.accounts.update', account.id), { preserveScroll: true, onSuccess: () => setEditing(false) });
    };

    if (editing) return (
        <form onSubmit={save} className="grid gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 md:grid-cols-[1fr_0.7fr_1fr_auto] md:items-end">
            <label className="text-xs font-semibold text-slate-600">Nom<input className={fieldClass} value={form.data.name} onChange={e => form.setData('name', e.target.value)} /></label>
            <label className="text-xs font-semibold text-slate-600">Type<select className={fieldClass} value={form.data.type} onChange={e => form.setData('type', e.target.value)}><option value="main">Principal</option><option value="bank">Banque</option><option value="cash">Espèces</option><option value="mobile_money">Mobile money</option><option value="savings">Épargne</option></select></label>
            <label className="text-xs font-semibold text-slate-600">Solde<input type="number" min="0" className={fieldClass} value={form.data.opening_balance_amount} onChange={e => form.setData('opening_balance_amount', Number(e.target.value))} /></label>
            <div className="flex flex-col gap-2"><label className="flex items-center gap-2 text-xs"><input type="checkbox" checked={form.data.included_in_planning} onChange={e => form.setData('included_in_planning', e.target.checked)} />Inclure</label><div className="flex gap-2"><button className="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white">Enregistrer</button><button type="button" onClick={() => setEditing(false)} className="rounded-lg border px-3 py-2 text-sm">Annuler</button></div></div>
        </form>
    );

    return (
        <div className="flex flex-col justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center">
            <div className="flex items-center gap-3"><AccountIcon account={account} /><div><p className="font-semibold text-slate-900">{account.name}</p><p className="text-xs text-slate-500">{account.included_in_planning ? 'Inclus dans les calculs' : 'Exclu des calculs'}</p></div></div>
            <div className="flex items-center gap-3"><strong>{money(account.opening_balance_amount)}</strong><button onClick={() => setEditing(true)} className="text-sm font-semibold text-emerald-700">Modifier</button>{canDelete && <button onClick={() => confirm('Supprimer ce compte ?') && router.delete(route('finances.accounts.destroy', account.id), { preserveScroll: true })} className="text-sm font-semibold text-rose-600">Supprimer</button>}</div>
        </div>
    );
}

function CashFlowRow({ flow }: { flow: CashFlow }) {
    const [editing, setEditing] = useState(false);
    const form = useForm({ direction: flow.direction, label: flow.label, amount: flow.amount, due_on: flow.due_on.slice(0, 10), is_essential: flow.is_essential });
    const save = (event: FormEvent) => { event.preventDefault(); form.put(route('finances.cash-flows.update', flow.id), { preserveScroll: true, onSuccess: () => setEditing(false) }); };

    if (editing) return (
        <form onSubmit={save} className="grid gap-3 rounded-2xl border border-cyan-200 bg-cyan-50 p-4 lg:grid-cols-[0.7fr_1.3fr_0.8fr_0.8fr_auto] lg:items-end">
            <label className="text-xs font-semibold">Type<select className={fieldClass} value={form.data.direction} onChange={e => form.setData('direction', e.target.value as 'income' | 'expense')}><option value="income">Revenu</option><option value="expense">Charge</option></select></label>
            <label className="text-xs font-semibold">Libellé<input className={fieldClass} value={form.data.label} onChange={e => form.setData('label', e.target.value)} /></label>
            <label className="text-xs font-semibold">Montant<input type="number" min="1" className={fieldClass} value={form.data.amount} onChange={e => form.setData('amount', Number(e.target.value))} /></label>
            <label className="text-xs font-semibold">Date<input type="date" className={fieldClass} value={form.data.due_on} onChange={e => form.setData('due_on', e.target.value)} /></label>
            <div className="flex flex-col gap-2"><label className="flex items-center gap-2 text-xs"><input type="checkbox" checked={form.data.is_essential} onChange={e => form.setData('is_essential', e.target.checked)} />Essentiel</label><div className="flex gap-2"><button className="rounded-lg bg-cyan-700 px-3 py-2 text-sm font-semibold text-white">Enregistrer</button><button type="button" onClick={() => setEditing(false)} className="rounded-lg border px-3 py-2 text-sm">Annuler</button></div></div>
        </form>
    );

    return (
        <div className="flex flex-col justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center">
            <div className="flex items-center gap-3"><span className={`rounded-full px-3 py-1 text-xs font-semibold ${flow.direction === 'income' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'}`}>{flow.direction === 'income' ? 'Revenu' : 'Charge'}</span><div><p className="font-semibold">{flow.label}</p><p className="text-xs text-slate-500">Prévu le {new Date(flow.due_on).toLocaleDateString('fr-FR')}</p></div></div>
            <div className="flex items-center gap-3"><strong className={flow.direction === 'income' ? 'text-emerald-700' : 'text-rose-700'}>{flow.direction === 'income' ? '+' : '-'}{money(flow.amount)}</strong><button onClick={() => setEditing(true)} className="text-sm font-semibold text-cyan-700">Modifier</button><button onClick={() => confirm('Supprimer cet élément planifié ?') && router.delete(route('finances.cash-flows.destroy', flow.id), { preserveScroll: true })} className="text-sm font-semibold text-rose-600">Supprimer</button></div>
        </div>
    );
}

export default function FinanceIndex({ accounts, cashFlows, position, budgetCycle }: { accounts: Account[]; cashFlows: CashFlow[]; position: Position; budgetCycle: BudgetCycle }) {
    const flash = usePage().props.flash;
    const accountForm = useForm({ name: '', type: 'bank', opening_balance_amount: 0, included_in_planning: true });
    const flowForm = useForm({ direction: 'expense' as 'income' | 'expense', label: '', amount: 0, due_on: new Date().toISOString().slice(0, 10), is_essential: true });
    const cycleForm = useForm({ ...budgetCycle });

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-slate-800">Revenus et charges</h2>}>
            <Head title="Revenus et charges" />
            <div className="min-h-[calc(100vh-8rem)] bg-slate-100 py-8"><div className="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                {flash?.success && <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{flash.success}</div>}
                <section className="mm-card p-6 sm:p-7"><div className="flex flex-col justify-between gap-5 lg:flex-row lg:items-end"><div><p className="mm-eyebrow">Cycle budgétaire</p><h3 className="mt-1 text-xl font-semibold text-[#302b45]">Mon salaire pilote mes dépenses</h3><p className="mt-2 max-w-2xl text-sm leading-6 text-slate-500">Définis le jour où commence ton cycle et le montant disponible jusqu’au cycle suivant. Ce budget n’est pas ajouté au compte bancaire.</p></div><form onSubmit={event => { event.preventDefault(); cycleForm.patch(route('finances.budget-cycle.update'), { preserveScroll: true }); }} className="grid min-w-0 gap-3 sm:grid-cols-[140px_220px_auto] sm:items-end"><label className="text-xs font-semibold text-slate-600">Jour de départ<input type="number" min="1" max="28" className={fieldClass} value={cycleForm.data.cycle_start_day} onChange={event => cycleForm.setData('cycle_start_day', Number(event.target.value))} /></label><label className="text-xs font-semibold text-slate-600">Budget du cycle<input type="number" min="1" className={fieldClass} value={cycleForm.data.monthly_budget_amount || ''} onChange={event => cycleForm.setData('monthly_budget_amount', Number(event.target.value))} placeholder="Ex. 300000" /></label><button disabled={cycleForm.processing} className="rounded-xl bg-[#6d4cc7] px-5 py-3 text-sm font-semibold text-white">Enregistrer</button><InputError message={cycleForm.errors.cycle_start_day || cycleForm.errors.monthly_budget_amount} className="sm:col-span-3" /></form></div></section>
                <section className="grid gap-4 md:grid-cols-3"><div className="rounded-2xl bg-slate-950 p-5 text-white"><p className="text-sm text-slate-400">Solde suivi</p><p className="mt-2 text-2xl font-semibold">{money(position.current_balance)}</p></div><div className="rounded-2xl bg-white p-5 shadow-sm"><p className="text-sm text-slate-500">Réellement dépensable</p><p className="mt-2 text-2xl font-semibold text-emerald-700">{money(position.spendable)}</p></div><div className="rounded-2xl bg-white p-5 shadow-sm"><p className="text-sm text-slate-500">Disponible quotidien</p><p className="mt-2 text-2xl font-semibold">{money(position.daily_available)}</p><p className="text-xs text-slate-400">pendant {position.days_to_cover} jours</p></div></section>

                <section className="grid gap-6 lg:grid-cols-[0.65fr_1.35fr]">
                    <form onSubmit={e => { e.preventDefault(); accountForm.post(route('finances.accounts.store'), { preserveScroll: true, onSuccess: () => accountForm.reset() }); }} className="h-fit rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h3 className="text-lg font-semibold">Ajouter un compte</h3><div className="mt-4 space-y-4"><label className="block text-sm font-semibold">Nom<input className={fieldClass} value={accountForm.data.name} onChange={e => accountForm.setData('name', e.target.value)} placeholder="Ex. Mobile Money" /></label><InputError message={accountForm.errors.name} /><label className="block text-sm font-semibold">Type<select className={fieldClass} value={accountForm.data.type} onChange={e => accountForm.setData('type', e.target.value)}><option value="bank">Banque</option><option value="cash">Espèces</option><option value="mobile_money">Mobile money</option><option value="savings">Épargne</option></select></label><label className="block text-sm font-semibold">Solde actuel<input type="number" min="0" className={fieldClass} value={accountForm.data.opening_balance_amount} onChange={e => accountForm.setData('opening_balance_amount', Number(e.target.value))} /></label><label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={accountForm.data.included_in_planning} onChange={e => accountForm.setData('included_in_planning', e.target.checked)} />Inclure dans le disponible</label><button disabled={accountForm.processing} className="w-full rounded-xl bg-slate-950 px-4 py-3 font-semibold text-white hover:bg-slate-800">Ajouter le compte</button></div></form>
                    <div><h3 className="text-lg font-semibold">Mes comptes</h3><div className="mt-4 space-y-3">{accounts.map(account => <AccountRow key={account.id} account={account} canDelete={accounts.length > 1} />)}</div></div>
                </section>

                <section className="grid gap-6 lg:grid-cols-[0.65fr_1.35fr]">
                    <form onSubmit={e => { e.preventDefault(); flowForm.post(route('finances.cash-flows.store'), { preserveScroll: true, onSuccess: () => flowForm.reset('label', 'amount') }); }} className="h-fit rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h3 className="text-lg font-semibold">Planifier un montant</h3><div className="mt-4 space-y-4"><label className="block text-sm font-semibold">Type<select className={fieldClass} value={flowForm.data.direction} onChange={e => flowForm.setData('direction', e.target.value as 'income' | 'expense')}><option value="expense">Charge à payer</option><option value="income">Revenu attendu</option></select></label><label className="block text-sm font-semibold">Libellé<input className={fieldClass} value={flowForm.data.label} onChange={e => flowForm.setData('label', e.target.value)} placeholder="Ex. Loyer" /></label><InputError message={flowForm.errors.label} /><label className="block text-sm font-semibold">Montant<input type="number" min="1" className={fieldClass} value={flowForm.data.amount} onChange={e => flowForm.setData('amount', Number(e.target.value))} /></label><label className="block text-sm font-semibold">Date prévue<input type="date" className={fieldClass} value={flowForm.data.due_on} onChange={e => flowForm.setData('due_on', e.target.value)} /></label><label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={flowForm.data.is_essential} onChange={e => flowForm.setData('is_essential', e.target.checked)} />Élément essentiel</label><button disabled={flowForm.processing} className="w-full rounded-xl bg-cyan-700 px-4 py-3 font-semibold text-white hover:bg-cyan-600">Ajouter à la prévision</button></div></form>
                    <div><h3 className="text-lg font-semibold">Prévisions à venir</h3><div className="mt-4 space-y-3">{cashFlows.length ? cashFlows.map(flow => <CashFlowRow key={flow.id} flow={flow} />) : <div className="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-slate-500">Aucun revenu ou charge planifié.</div>}</div></div>
                </section>
            </div></div>
        </AuthenticatedLayout>
    );
}
