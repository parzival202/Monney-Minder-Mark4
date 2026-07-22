import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent } from 'react';

type Connection = { chat_id: string; telegram_username?: string; is_active: boolean; verified_at?: string; last_update_at?: string; webhook_url: string; notification_preferences?: { daily_summary?: boolean; decision_alerts?: boolean } };
type Message = { id: number; direction: 'incoming' | 'outgoing'; text?: string; imported: boolean; sent_at?: string };

export default function Telegram({ connection, messages }: { connection: Connection | null; messages: Message[] }) {
    const flash = usePage().props.flash as { success?: string } | undefined;
    const form = useForm({ bot_token: '', chat_id: connection?.chat_id || '', telegram_username: connection?.telegram_username || '' });
    const preferences = useForm({ daily_summary: connection?.notification_preferences?.daily_summary ?? true, decision_alerts: connection?.notification_preferences?.decision_alerts ?? true });
    const submit = (event: FormEvent) => { event.preventDefault(); form.post(route('telegram.store'), { preserveScroll: true, onSuccess: () => form.reset('bot_token') }); };
    return <AuthenticatedLayout header={<h2 className="text-xl font-semibold text-slate-900">Assistant Telegram</h2>}>
        <Head title="Telegram" />
        <main className="mx-auto max-w-6xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            {flash?.success && <div className="rounded-2xl bg-emerald-50 px-5 py-4 text-emerald-800">{flash.success}</div>}
            <section className="mm-card overflow-hidden p-7">
                <p className="mm-eyebrow">Décider avant de dépenser</p><h1 className="mt-2 text-3xl font-semibold text-[#302b45]">MoneyMinder dans Telegram</h1>
                <p className="mt-3 max-w-2xl text-[#746f86]">Crée un projet en trois messages, reçois un verdict basé sur tes vraies finances, puis réserve le budget en un bouton.</p>
                <div className="mt-5 rounded-2xl bg-[#f3f0f8] p-4 font-mono text-sm leading-7 text-[#5a3da9]"><div>/projet Sortie du week-end</div><div>/budget 40000</div><div>/date 27/07/2027</div></div>
            </section>
            <div className="grid gap-6 lg:grid-cols-[0.85fr_1.15fr]">
                <section className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 className="text-xl font-semibold">{connection ? 'Connexion du bot' : 'Connecter mon bot'}</h2><p className="mt-2 text-sm text-slate-500">Le jeton est chiffré dans la base et n’est jamais renvoyé au navigateur.</p>
                    <form onSubmit={submit} className="mt-5 space-y-4">
                        <label className="block text-sm font-semibold">Jeton donné par BotFather<input type="password" className="mt-1 w-full rounded-xl border-slate-300" value={form.data.bot_token} onChange={e => form.setData('bot_token', e.target.value)} placeholder="123456:ABC…" /></label>
                        <label className="block text-sm font-semibold">Identifiant du chat<input className="mt-1 w-full rounded-xl border-slate-300" value={form.data.chat_id} onChange={e => form.setData('chat_id', e.target.value)} placeholder="Ex. 123456789" /></label>
                        <label className="block text-sm font-semibold">Nom Telegram facultatif<input className="mt-1 w-full rounded-xl border-slate-300" value={form.data.telegram_username} onChange={e => form.setData('telegram_username', e.target.value)} placeholder="@franck" /></label>
                        <InputError message={form.errors.bot_token || form.errors.chat_id} /><button disabled={form.processing} className="w-full rounded-xl bg-[#6d4cc7] px-4 py-3 font-semibold text-white">Enregistrer la connexion</button>
                    </form>
                    {connection && <div className="mt-5 space-y-3 border-t pt-5"><div className="flex items-center justify-between text-sm"><span className="text-slate-500">État</span><strong className={connection.verified_at ? 'text-emerald-600' : 'text-amber-600'}>{connection.verified_at ? 'Activé' : 'À activer'}</strong></div><button onClick={() => router.post(route('telegram.activate'))} className="w-full rounded-xl bg-cyan-600 px-4 py-3 font-semibold text-white">Activer le bot Telegram</button><div className="rounded-2xl bg-slate-50 p-4 text-sm"><p className="font-semibold">Notifications</p><label className="mt-3 flex items-center gap-3"><input type="checkbox" checked={preferences.data.daily_summary} onChange={e => preferences.setData('daily_summary', e.target.checked)} /> Résumé chaque matin à 7 h 30</label><label className="mt-3 flex items-center gap-3"><input type="checkbox" checked={preferences.data.decision_alerts} onChange={e => preferences.setData('decision_alerts', e.target.checked)} /> Alertes de décisions et projets</label><button onClick={() => preferences.patch(route('telegram.preferences'), { preserveScroll: true })} className="mt-4 font-semibold text-cyan-700">Enregistrer les préférences</button></div><button onClick={() => confirm('Déconnecter ce bot ?') && router.delete(route('telegram.destroy'))} className="w-full rounded-xl border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-600">Déconnecter</button></div>}
                </section>
                <section className="rounded-3xl bg-white p-6 shadow-sm ring-1 ring-slate-200"><div className="flex items-center justify-between"><div><p className="text-sm font-semibold text-slate-500">Mark 3 + Mark 4</p><h2 className="text-xl font-semibold">Historique Telegram</h2></div><span className="rounded-full bg-slate-100 px-3 py-1 text-xs">{messages.length} récents</span></div>
                    <div className="mt-5 space-y-3">{messages.length ? messages.map(message => <div key={message.id} className={`flex ${message.direction === 'outgoing' ? 'justify-start' : 'justify-end'}`}><div className={`max-w-[85%] rounded-2xl px-4 py-3 text-sm ${message.direction === 'outgoing' ? 'bg-slate-100 text-slate-700' : 'bg-cyan-600 text-white'}`}><p className="whitespace-pre-wrap">{message.text || 'Message sans texte'}</p><p className={`mt-1 text-[11px] ${message.direction === 'outgoing' ? 'text-slate-400' : 'text-cyan-100'}`}>{message.imported ? 'Importé de Mark 3 · ' : ''}{message.sent_at ? new Date(message.sent_at).toLocaleString('fr-FR') : ''}</p></div></div>) : <div className="rounded-2xl border border-dashed p-10 text-center text-slate-500">Les décisions et notifications Telegram apparaîtront ici.</div>}</div>
                </section>
            </div>
        </main>
    </AuthenticatedLayout>;
}
