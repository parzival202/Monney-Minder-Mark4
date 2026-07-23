import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { ReactNode, useState } from 'react';

type Category = { name: string; amount: number; count: number };
type Project = { id: number; name: string; target_amount: number; target_date: string; status: string; source: string; reserved_amount: number };
type Transaction = { id: number; description: string; amount: number; occurred_on: string; category?: string; account?: string; purchase_nature: string; source: string };
type Archive = {
    id: number;
    cycle_start: string;
    cycle_end: string;
    budget_amount: number;
    total_spent_amount: number;
    remaining_amount: number;
    overspent_amount: number;
    impulsive_amount: number;
    essential_amount: number;
    transaction_count: number;
    no_spend_days: number;
    categories: Category[];
    projects: Project[];
    transactions: Transaction[];
    archived_automatically: boolean;
};

const money = (value: number) => new Intl.NumberFormat('fr-FR').format(value) + ' FCFA';
const date = (value: string) => new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(`${value}T00:00:00`));

export default function Index({ archives, availableCycles, automatic }: { archives: Archive[]; availableCycles: { start: string; end: string; label: string }[]; automatic: boolean }) {
    const [open, setOpen] = useState<number | null>(null);

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-slate-800">Archives des cycles</h2>}>
            <Head title="Archives" />
            <div className="min-h-[calc(100vh-8rem)] bg-[#f6f4fb] py-8">
                <div className="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
                    <section className="rounded-3xl border border-[#e8e3f0] bg-white p-6 shadow-sm">
                        <div className="flex flex-col justify-between gap-5 sm:flex-row sm:items-center">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.18em] text-[#7652d6]">Mémoire financière</p>
                                <h1 className="mt-2 text-2xl font-semibold text-[#211c3b]">Un bilan immuable pour chaque cycle</h1>
                                <p className="mt-2 max-w-2xl text-sm text-slate-500">Chaque archive garde le budget, les dépenses, les catégories et les projets du cycle. Tes opérations restent disponibles dans l’application.</p>
                            </div>
                            <label className="flex cursor-pointer items-center gap-3 rounded-2xl bg-[#f5f1fb] px-4 py-3 text-sm font-medium text-[#352c50]">
                                <input
                                    type="checkbox"
                                    checked={automatic}
                                    onChange={(event) => router.patch(route('archives.preferences.update'), { automatic: event.target.checked }, { preserveScroll: true })}
                                    className="h-5 w-5 rounded border-slate-300 text-[#6f4bd3] focus:ring-[#6f4bd3]"
                                />
                                Archivage automatique
                            </label>
                        </div>

                        {availableCycles.length > 0 && (
                            <div className="mt-6 border-t border-slate-100 pt-5">
                                <p className="mb-3 text-sm font-medium text-slate-700">Cycles terminés à archiver</p>
                                <div className="flex flex-wrap gap-2">
                                    {availableCycles.map((cycle) => (
                                        <button
                                            key={cycle.start}
                                            onClick={() => router.post(route('archives.store'), { cycle_start: cycle.start }, { preserveScroll: true })}
                                            className="rounded-full border border-[#dcd3ef] bg-white px-4 py-2 text-sm text-[#5c429c] transition hover:bg-[#f4effc]"
                                        >
                                            + {cycle.label}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}
                    </section>

                    {archives.length === 0 ? (
                        <div className="rounded-3xl border border-dashed border-[#dcd5e8] bg-white/70 px-6 py-16 text-center text-slate-500">
                            Aucune archive pour le moment. Le premier cycle terminé pourra être archivé ici.
                        </div>
                    ) : archives.map((archive) => (
                        <article key={archive.id} className="overflow-hidden rounded-3xl border border-[#e8e3f0] bg-white shadow-sm">
                            <button onClick={() => setOpen(open === archive.id ? null : archive.id)} className="w-full p-6 text-left">
                                <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <h2 className="text-lg font-semibold text-[#211c3b]">{date(archive.cycle_start)} – {date(archive.cycle_end)}</h2>
                                            <span className="rounded-full bg-[#f1edf8] px-2.5 py-1 text-xs text-[#6b529d]">{archive.archived_automatically ? 'Automatique' : 'Manuel'}</span>
                                        </div>
                                        <p className="mt-1 text-sm text-slate-500">{archive.transaction_count} dépense(s) · {archive.no_spend_days} jour(s) sans dépense · {archive.projects.length} projet(s)</p>
                                    </div>
                                    <span className="text-sm font-medium text-[#6f4bd3]">{open === archive.id ? 'Réduire' : 'Voir le détail'}</span>
                                </div>
                                <div className="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
                                    <Metric label="Budget" value={money(archive.budget_amount)} />
                                    <Metric label="Dépensé" value={money(archive.total_spent_amount)} />
                                    <Metric label={archive.overspent_amount > 0 ? 'Dépassement' : 'Restant'} value={money(archive.overspent_amount || archive.remaining_amount)} danger={archive.overspent_amount > 0} />
                                    <Metric label="Impulsif" value={money(archive.impulsive_amount)} />
                                </div>
                            </button>

                            {open === archive.id && (
                                <div className="grid gap-6 border-t border-slate-100 bg-[#fcfbfe] p-6 lg:grid-cols-2">
                                    <Detail title="Catégories" empty="Aucune dépense catégorisée.">
                                        {archive.categories.map((category) => (
                                            <Row key={category.name} left={`${category.name} · ${category.count}`} right={money(category.amount)} />
                                        ))}
                                    </Detail>
                                    <Detail title="Projets du cycle" empty="Aucun projet lié à ce cycle.">
                                        {archive.projects.map((project) => (
                                            <Row key={project.id} left={`${project.name} · ${project.source === 'telegram' ? 'Telegram' : 'Application'}`} right={money(project.target_amount)} />
                                        ))}
                                    </Detail>
                                    <div className="lg:col-span-2">
                                        <Detail title="Dépenses enregistrées" empty="Aucune dépense pendant ce cycle.">
                                            {archive.transactions.map((transaction) => (
                                                <Row key={transaction.id} left={`${date(transaction.occurred_on)} · ${transaction.description}${transaction.category ? ` · ${transaction.category}` : ''}`} right={money(transaction.amount)} />
                                            ))}
                                        </Detail>
                                    </div>
                                </div>
                            )}
                        </article>
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function Metric({ label, value, danger = false }: { label: string; value: string; danger?: boolean }) {
    return <div className="rounded-2xl bg-[#f7f5fa] p-4"><p className="text-xs uppercase tracking-wide text-slate-400">{label}</p><p className={`mt-1 font-semibold ${danger ? 'text-rose-600' : 'text-[#2b2442]'}`}>{value}</p></div>;
}

function Detail({ title, empty, children }: { title: string; empty: string; children: ReactNode }) {
    const hasChildren = Array.isArray(children) ? children.length > 0 : Boolean(children);
    return <div><h3 className="mb-3 font-semibold text-[#302845]">{title}</h3><div className="space-y-2">{hasChildren ? children : <p className="text-sm text-slate-400">{empty}</p>}</div></div>;
}

function Row({ left, right }: { left: string; right: string }) {
    return <div className="flex items-center justify-between gap-4 rounded-xl bg-white px-4 py-3 text-sm"><span className="text-slate-600">{left}</span><strong className="whitespace-nowrap text-[#302845]">{right}</strong></div>;
}
