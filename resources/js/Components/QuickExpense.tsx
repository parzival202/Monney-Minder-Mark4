import InputError from '@/Components/InputError';
import AccountIcon from '@/Components/AccountIcon';
import { useForm, usePage } from '@inertiajs/react';
import { FormEvent, useEffect, useState } from 'react';

type Item = { id: number; name: string };

export default function QuickExpense() {
    const { quickExpense } = usePage().props as unknown as { quickExpense?: { accounts: Item[]; categories: Item[] } };
    const [open, setOpen] = useState(false);
    const accounts = quickExpense?.accounts ?? [];
    const categories = quickExpense?.categories ?? [];
    const form = useForm({ financial_account_id: accounts[0]?.id ?? 0, expense_category_id: categories[0]?.id ?? 0, description: '', amount: 0, occurred_on: new Date().toISOString().slice(0, 10), purchase_nature: 'planned' });

    useEffect(() => {
        if (!form.data.financial_account_id && accounts[0]) form.setData('financial_account_id', accounts[0].id);
        if (!form.data.expense_category_id && categories[0]) form.setData('expense_category_id', categories[0].id);
    }, [quickExpense]);

    if (!quickExpense) return null;
    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post(route('expenses.store'), { preserveScroll: true, onSuccess: () => { form.reset('description', 'amount'); setOpen(false); } });
    };
    const field = 'mt-1 block w-full rounded-xl border-slate-300 text-sm focus:border-[#8d73cf] focus:ring-[#8d73cf]';

    return <>
        {open && <div className="fixed inset-0 z-50 flex items-end justify-center bg-[#241f35]/35 p-3 backdrop-blur-sm sm:items-center" onMouseDown={() => setOpen(false)}>
            <section role="dialog" aria-modal="true" aria-label="Ajouter une dépense" onMouseDown={event => event.stopPropagation()} className="w-full max-w-lg rounded-[1.5rem] bg-white p-5 shadow-2xl sm:p-7">
                <div className="flex items-start justify-between"><div><p className="text-xs font-bold uppercase tracking-[.16em] text-[#8d73cf]">Ajout rapide</p><h2 className="mt-1 text-2xl font-semibold text-[#302b45]">Nouvelle dépense</h2></div><button type="button" onClick={() => setOpen(false)} className="rounded-full bg-[#f3f0f8] px-3 py-1.5 text-lg text-[#746f86]" aria-label="Fermer">×</button></div>
                {accounts.length && categories.length ? <form onSubmit={submit} className="mt-6 grid gap-4 sm:grid-cols-2">
                    <label className="text-sm font-semibold sm:col-span-2">Qu’as-tu acheté ?<input autoFocus className={field} value={form.data.description} onChange={event => form.setData('description', event.target.value)} placeholder="Ex. Déjeuner" /></label>
                    <label className="text-sm font-semibold">Montant<input type="number" min="1" inputMode="numeric" className={field} value={form.data.amount || ''} onChange={event => form.setData('amount', Number(event.target.value))} placeholder="0" /></label>
                    <label className="text-sm font-semibold">Catégorie<select className={field} value={form.data.expense_category_id} onChange={event => form.setData('expense_category_id', Number(event.target.value))}>{categories.map(item => <option key={item.id} value={item.id}>{item.name}</option>)}</select></label>
                    <fieldset className="sm:col-span-2"><legend className="text-sm font-semibold">Compte utilisé</legend><div className="mt-2 grid gap-2 sm:grid-cols-3">{accounts.map(item=><button type="button" key={item.id} onClick={()=>form.setData('financial_account_id',item.id)} className={`flex items-center gap-2 rounded-xl border p-2.5 text-left text-sm font-semibold transition ${form.data.financial_account_id===item.id?'border-[#8d73cf] bg-[#f3f0f8] text-[#5a3da9]':'border-slate-200 bg-white text-slate-600'}`}><AccountIcon account={item} className="h-8 w-8 shrink-0"/><span className="truncate">{item.name}</span></button>)}</div></fieldset>
                    <label className="text-sm font-semibold">Type<select className={field} value={form.data.purchase_nature} onChange={event => form.setData('purchase_nature', event.target.value)}><option value="planned">Prévue</option><option value="unplanned_necessary">Imprévue nécessaire</option><option value="impulsive">Impulsive</option></select></label>
                    <InputError message={form.errors.description || form.errors.amount || form.errors.financial_account_id || form.errors.expense_category_id} className="sm:col-span-2" />
                    <button disabled={form.processing} className="rounded-xl bg-[#6d4cc7] px-5 py-3 font-semibold text-white hover:bg-[#5f42ae] sm:col-span-2">{form.processing ? 'Enregistrement…' : 'Enregistrer la dépense'}</button>
                </form> : <div className="mt-6 rounded-xl bg-amber-50 p-4 text-sm text-amber-800">Ajoute d’abord un compte et une catégorie depuis la page Dépenses.</div>}
            </section>
        </div>}
        <button type="button" onClick={() => setOpen(true)} className="fixed bottom-5 right-5 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-[#6d4cc7] text-3xl font-light text-white shadow-[0_12px_30px_rgba(109,76,199,.4)] transition hover:-translate-y-1 hover:bg-[#5f42ae] focus:outline-none focus:ring-4 focus:ring-[#dcd2f3] sm:bottom-7 sm:right-7" aria-label="Ajouter rapidement une dépense" title="Ajouter une dépense">+</button>
    </>;
}
