import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import AccountIcon from '@/Components/AccountIcon';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';

type Account = { id: number; name: string };
type Category = { id: number; name: string; color: string; is_essential: boolean };
type Expense = {
    id: number;
    financial_account_id: number;
    expense_category_id: number;
    description: string;
    amount: number;
    occurred_on: string;
    purchase_nature: 'planned' | 'unplanned_necessary' | 'impulsive';
    financial_account?: Account;
    expense_category?: Pick<Category, 'id' | 'name' | 'color'>;
};
type Position = { spendable: number; daily_available: number; essential_daily_target: number; days_to_cover: number };

const money = (value: number) => new Intl.NumberFormat('fr-FR').format(value) + ' FCFA';
const fieldClass = 'mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500';
const natureLabels = { planned: 'Prévue', unplanned_necessary: 'Imprévue nécessaire', impulsive: 'Impulsive' };

function ExpenseRow({ expense, accounts, categories }: { expense: Expense; accounts: Account[]; categories: Category[] }) {
    const [editing, setEditing] = useState(false);
    const form = useForm({
        financial_account_id: expense.financial_account_id,
        expense_category_id: expense.expense_category_id,
        description: expense.description,
        amount: expense.amount,
        occurred_on: expense.occurred_on.slice(0, 10),
        purchase_nature: expense.purchase_nature,
    });

    const save = (event: FormEvent) => {
        event.preventDefault();
        form.put(route('expenses.update', expense.id), { preserveScroll: true, onSuccess: () => setEditing(false) });
    };

    if (editing) return (
        <form onSubmit={save} className="grid gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 lg:grid-cols-6 lg:items-end">
            <label className="text-xs font-semibold lg:col-span-2">Description<input className={fieldClass} value={form.data.description} onChange={e => form.setData('description', e.target.value)} /></label>
            <label className="text-xs font-semibold">Montant<input type="number" min="1" className={fieldClass} value={form.data.amount} onChange={e => form.setData('amount', Number(e.target.value))} /></label>
            <label className="text-xs font-semibold">Catégorie<select className={fieldClass} value={form.data.expense_category_id} onChange={e => form.setData('expense_category_id', Number(e.target.value))}>{categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}</select></label>
            <label className="text-xs font-semibold">Nature<select className={fieldClass} value={form.data.purchase_nature} onChange={e => form.setData('purchase_nature', e.target.value as Expense['purchase_nature'])}>{Object.entries(natureLabels).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></label>
            <div className="flex gap-2"><button className="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white">Enregistrer</button><button type="button" onClick={() => setEditing(false)} className="rounded-lg border px-3 py-2 text-xs">Annuler</button></div>
        </form>
    );

    return (
        <article className="flex flex-col justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center">
            <div className="flex items-center gap-3"><AccountIcon account={expense.financial_account} className="h-10 w-10"/><div><p className="font-semibold text-slate-900">{expense.description}</p><p className="text-xs text-slate-500"><span className="mr-1.5 inline-block h-2 w-2 rounded-full" style={{ backgroundColor: expense.expense_category?.color ?? '#64748b' }}/>{expense.expense_category?.name ?? 'Catégorie retirée'} · {expense.financial_account?.name} · {new Date(expense.occurred_on).toLocaleDateString('fr-FR')}</p></div></div>
            <div className="flex flex-wrap items-center gap-3"><span className={`rounded-full px-3 py-1 text-xs font-semibold ${expense.purchase_nature === 'impulsive' ? 'bg-rose-100 text-rose-700' : expense.purchase_nature === 'unplanned_necessary' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600'}`}>{natureLabels[expense.purchase_nature]}</span><strong className="text-rose-700">−{money(expense.amount)}</strong><button onClick={() => setEditing(true)} className="text-sm font-semibold text-emerald-700">Modifier</button><button onClick={() => confirm('Supprimer cette dépense ? Le disponible sera restauré.') && router.delete(route('expenses.destroy', expense.id), { preserveScroll: true })} className="text-sm font-semibold text-rose-600">Supprimer</button></div>
        </article>
    );
}

export default function ExpensesIndex({ accounts, categories, expenses, position, summary }: { accounts: Account[]; categories: Category[]; expenses: Expense[]; position: Position; summary: { month_total: number; impulsive_total: number } }) {
    const flash = usePage().props.flash;
    const expenseForm = useForm({ financial_account_id: accounts[0]?.id ?? 0, expense_category_id: categories[0]?.id ?? 0, description: '', amount: 0, occurred_on: new Date().toISOString().slice(0, 10), purchase_nature: 'planned' as Expense['purchase_nature'] });
    const categoryForm = useForm({ name: '', color: '#64748b', is_essential: false });

    const submitExpense = (event: FormEvent) => {
        event.preventDefault();
        expenseForm.post(route('expenses.store'), { preserveScroll: true, onSuccess: () => expenseForm.reset('description', 'amount') });
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-slate-800">Dépenses</h2>}>
            <Head title="Mes dépenses" />
            <div className="min-h-[calc(100vh-8rem)] py-8"><div className="mx-auto max-w-7xl space-y-7 px-4 sm:px-6 lg:px-8">
                {flash?.success && <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{flash.success}</div>}
                <section className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div className="mm-card p-5"><p className="mm-eyebrow">Disponible réel</p><p className="mt-2 text-2xl font-semibold text-[#302b45]">{money(position.spendable)}</p></div>
                    <div className="rounded-2xl bg-white p-5 shadow-sm"><p className="text-xs uppercase tracking-wider text-slate-400">Par jour</p><p className="mt-2 text-2xl font-semibold text-emerald-700">{money(position.daily_available)}</p><p className="text-xs text-slate-400">sur {position.days_to_cover} jours</p></div>
                    <div className="rounded-2xl bg-white p-5 shadow-sm"><p className="text-xs uppercase tracking-wider text-slate-400">Dépensé ce mois</p><p className="mt-2 text-2xl font-semibold">{money(summary.month_total)}</p></div>
                    <div className="rounded-2xl border border-rose-200 bg-rose-50 p-5"><p className="text-xs uppercase tracking-wider text-rose-500">Achats impulsifs</p><p className="mt-2 text-2xl font-semibold text-rose-700">{money(summary.impulsive_total)}</p></div>
                </section>

                <section className="mm-card p-6 sm:p-8">
                    <div className="mb-6"><p className="mm-eyebrow">Ajout rapide</p><h3 className="mt-1 text-2xl font-semibold text-[#302b45]">Qu’as-tu payé ?</h3></div>
                    <form onSubmit={submitExpense} className="grid gap-5 lg:grid-cols-6 lg:items-end">
                        <label className="text-sm font-semibold lg:col-span-2">Description<input autoFocus className={fieldClass} value={expenseForm.data.description} onChange={e => expenseForm.setData('description', e.target.value)} placeholder="Ex. Déjeuner" /></label>
                        <label className="text-sm font-semibold">Montant<input type="number" min="1" className={fieldClass} value={expenseForm.data.amount || ''} onChange={e => expenseForm.setData('amount', Number(e.target.value))} /></label>
                        <label className="text-sm font-semibold">Compte<select className={fieldClass} value={expenseForm.data.financial_account_id} onChange={e => expenseForm.setData('financial_account_id', Number(e.target.value))}>{accounts.map(a => <option key={a.id} value={a.id}>{a.name}</option>)}</select></label>
                        <label className="text-sm font-semibold">Catégorie<select className={fieldClass} value={expenseForm.data.expense_category_id} onChange={e => expenseForm.setData('expense_category_id', Number(e.target.value))}>{categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}</select></label>
                        <label className="text-sm font-semibold">Date<input type="date" max={new Date().toISOString().slice(0, 10)} className={fieldClass} value={expenseForm.data.occurred_on} onChange={e => expenseForm.setData('occurred_on', e.target.value)} /></label>
                        <div className="lg:col-span-5"><p className="mb-2 text-sm font-semibold">Cette dépense était-elle prévue ?</p><div className="grid gap-2 sm:grid-cols-3">{Object.entries(natureLabels).map(([value, label]) => <button type="button" key={value} onClick={() => expenseForm.setData('purchase_nature', value as Expense['purchase_nature'])} className={`rounded-xl border px-4 py-3 text-sm font-semibold transition ${expenseForm.data.purchase_nature === value ? 'border-[#9d87d8] bg-[#eee9f8] text-[#5a3da9]' : 'border-slate-200 bg-white text-slate-500'}`}>{label}</button>)}</div></div>
                        <button disabled={expenseForm.processing || !accounts.length} className="rounded-xl bg-[#6d4cc7] px-5 py-3 font-semibold text-white hover:bg-[#5f42ae]">Enregistrer</button>
                    </form>
                    <InputError message={expenseForm.errors.description || expenseForm.errors.amount || expenseForm.errors.financial_account_id} className="mt-3" />
                </section>

                <section className="grid gap-6 lg:grid-cols-[1.4fr_0.6fr]">
                    <div><div className="flex items-end justify-between"><div><p className="text-sm font-semibold text-slate-500">Historique récent</p><h3 className="text-xl font-semibold">Tes dernières dépenses</h3></div><span className="text-sm text-slate-500">{expenses.length} affichée(s)</span></div><div className="mt-4 space-y-3">{expenses.length ? expenses.map(expense => <ExpenseRow key={expense.id} expense={expense} accounts={accounts} categories={categories} />) : <div className="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">Aucune dépense enregistrée. Ton disponible est encore intact.</div>}</div></div>
                    <aside className="h-fit rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h3 className="font-semibold">Nouvelle catégorie</h3><form onSubmit={e => { e.preventDefault(); categoryForm.post(route('expenses.categories.store'), { preserveScroll: true, onSuccess: () => categoryForm.reset('name') }); }} className="mt-4 space-y-3"><input className={fieldClass} value={categoryForm.data.name} onChange={e => categoryForm.setData('name', e.target.value)} placeholder="Nom de la catégorie" /><div className="flex items-center gap-3"><input type="color" value={categoryForm.data.color} onChange={e => categoryForm.setData('color', e.target.value)} className="h-10 w-14 rounded border" /><label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={categoryForm.data.is_essential} onChange={e => categoryForm.setData('is_essential', e.target.checked)} />Essentielle</label></div><button className="w-full rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Ajouter</button></form><InputError message={categoryForm.errors.name} className="mt-2" /><div className="mt-5 flex flex-wrap gap-2">{categories.map(category => <span key={category.id} className="inline-flex items-center gap-2 rounded-full border bg-slate-50 px-3 py-1.5 text-xs"><span className="h-2.5 w-2.5 rounded-full" style={{ backgroundColor: category.color }} />{category.name}<button title="Retirer" onClick={() => confirm(`Retirer la catégorie ${category.name} ?`) && router.delete(route('expenses.categories.destroy', category.id), { preserveScroll: true })} className="font-bold text-slate-400 hover:text-rose-600">×</button></span>)}</div></aside>
                </section>
            </div></div>
        </AuthenticatedLayout>
    );
}
