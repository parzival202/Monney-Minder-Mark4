import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

type Defaults = {
    account_name: string;
    guard_mode: string;
    next_income_on: string;
};

export default function FinancialOnboarding({ defaults }: { defaults: Defaults }) {
    const { data, setData, post, processing, errors } = useForm({
        account_name: defaults.account_name,
        opening_balance: '',
        expected_income: '',
        next_income_on: defaults.next_income_on,
        commitments_before_income: '',
        protected_savings: '',
        safety_buffer: '',
        essential_daily_target: '',
        guard_mode: defaults.guard_mode,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('onboarding.financial.store'));
    };

    const moneyField = (
        name: 'opening_balance' | 'expected_income' | 'commitments_before_income' | 'protected_savings' | 'safety_buffer' | 'essential_daily_target',
        label: string,
        help: string,
    ) => (
        <div>
            <InputLabel htmlFor={name} value={label} />
            <div className="relative mt-1">
                <TextInput
                    id={name}
                    type="number"
                    min="0"
                    step="1"
                    className="block w-full pr-20"
                    value={data[name]}
                    onChange={(event) => setData(name, event.target.value)}
                    required
                />
                <span className="pointer-events-none absolute inset-y-0 right-4 flex items-center text-sm font-semibold text-slate-400">FCFA</span>
            </div>
            <p className="mt-1 text-xs leading-5 text-slate-500">{help}</p>
            <InputError message={errors[name]} className="mt-1" />
        </div>
    );

    return (
        <div className="min-h-screen bg-slate-950 text-slate-100">
            <Head title="Situation financière initiale" />
            <div className="mx-auto grid min-h-screen max-w-7xl lg:grid-cols-[0.8fr_1.2fr]">
                <aside className="flex flex-col justify-between border-b border-white/10 bg-gradient-to-br from-emerald-500/20 via-slate-950 to-cyan-500/10 p-8 lg:border-b-0 lg:border-r lg:p-12">
                    <div>
                        <div className="mb-12 inline-flex items-center gap-3 rounded-full border border-emerald-300/20 bg-emerald-300/10 px-4 py-2 text-sm text-emerald-200">
                            <span className="h-2 w-2 rounded-full bg-emerald-400" />
                            MoneyMinder Mark 4
                        </div>
                        <p className="text-sm font-semibold uppercase tracking-[0.25em] text-emerald-300">Étape 1</p>
                        <h1 className="mt-4 max-w-lg text-4xl font-semibold leading-tight sm:text-5xl">Définissons ce que tu peux vraiment dépenser.</h1>
                        <p className="mt-6 max-w-xl text-base leading-7 text-slate-300">Ces montants servent de point de départ. Ils pourront tous être ajustés plus tard. MoneyMinder ne confondra jamais ton solde avec ton argent disponible.</p>
                    </div>
                    <div className="mt-10 rounded-2xl border border-white/10 bg-white/5 p-5 text-sm leading-6 text-slate-300">
                        <strong className="text-white">Confidentialité :</strong> tes données financières restent liées uniquement à ton compte. Aucun autre utilisateur ne peut les consulter.
                    </div>
                </aside>

                <main className="bg-slate-50 p-6 text-slate-900 sm:p-10 lg:p-12">
                    <form onSubmit={submit} className="mx-auto max-w-3xl space-y-8">
                        <section>
                            <p className="text-sm font-semibold text-emerald-700">Situation actuelle</p>
                            <h2 className="mt-1 text-2xl font-semibold">Ton point de départ</h2>
                            <div className="mt-5 grid gap-5 sm:grid-cols-2">
                                <div>
                                    <InputLabel htmlFor="account_name" value="Nom du compte principal" />
                                    <TextInput id="account_name" className="mt-1 block w-full" value={data.account_name} onChange={(event) => setData('account_name', event.target.value)} required />
                                    <InputError message={errors.account_name} className="mt-1" />
                                </div>
                                {moneyField('opening_balance', 'Solde disponible aujourd’hui', 'Banque, espèces et mobile money inclus si tu veux les suivre ensemble.')}
                            </div>
                        </section>

                        <section className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <p className="text-sm font-semibold text-cyan-700">Prochain horizon</p>
                            <h2 className="mt-1 text-xl font-semibold">Jusqu’à la prochaine rentrée d’argent</h2>
                            <div className="mt-5 grid gap-5 sm:grid-cols-2">
                                {moneyField('expected_income', 'Montant attendu', 'Mets 0 si aucun revenu n’est encore confirmé.')}
                                <div>
                                    <InputLabel htmlFor="next_income_on" value="Date prévue" />
                                    <TextInput id="next_income_on" type="date" className="mt-1 block w-full" value={data.next_income_on} onChange={(event) => setData('next_income_on', event.target.value)} required />
                                    <InputError message={errors.next_income_on} className="mt-1" />
                                </div>
                                {moneyField('commitments_before_income', 'Charges restant à payer', 'Loyer, factures, remboursements et autres obligations avant cette date.')}
                                {moneyField('essential_daily_target', 'Besoin essentiel par jour', 'Transport, repas et dépenses réellement nécessaires au quotidien.')}
                            </div>
                        </section>

                        <section>
                            <p className="text-sm font-semibold text-violet-700">Protection</p>
                            <h2 className="mt-1 text-xl font-semibold">L’argent qui ne doit pas être dépensé</h2>
                            <div className="mt-5 grid gap-5 sm:grid-cols-2">
                                {moneyField('protected_savings', 'Épargne protégée', 'Cette somme reste visible, mais elle est exclue du disponible.')}
                                {moneyField('safety_buffer', 'Marge de sécurité', 'Une réserve supplémentaire pour les imprévus du cycle.')}
                            </div>
                        </section>

                        <section>
                            <InputLabel htmlFor="guard_mode" value="Niveau d’accompagnement" />
                            <div className="mt-3 grid gap-3 sm:grid-cols-3">
                                {[
                                    ['flexible', 'Flexible', 'Informe sans insister.'],
                                    ['balanced', 'Équilibré', 'Avertit et propose des alternatives.'],
                                    ['strict', 'Strict', 'Ajoute plus de friction aux achats risqués.'],
                                ].map(([value, title, description]) => (
                                    <label key={value} className={`cursor-pointer rounded-2xl border p-4 transition ${data.guard_mode === value ? 'border-emerald-500 bg-emerald-50 ring-2 ring-emerald-200' : 'border-slate-200 bg-white hover:border-slate-300'}`}>
                                        <input type="radio" name="guard_mode" value={value} checked={data.guard_mode === value} onChange={() => setData('guard_mode', value)} className="sr-only" />
                                        <span className="block font-semibold">{title}</span>
                                        <span className="mt-1 block text-xs leading-5 text-slate-500">{description}</span>
                                    </label>
                                ))}
                            </div>
                            <InputError message={errors.guard_mode} className="mt-1" />
                        </section>

                        <div className="flex items-center justify-between border-t border-slate-200 pt-6">
                            <p className="max-w-md text-xs leading-5 text-slate-500">Aucune dépense ne sera créée. Nous préparons seulement ton premier calcul.</p>
                            <PrimaryButton disabled={processing} className="bg-emerald-600 px-6 py-3 hover:bg-emerald-500 focus:bg-emerald-500 active:bg-emerald-700">
                                {processing ? 'Calcul en cours…' : 'Calculer mon disponible'}
                            </PrimaryButton>
                        </div>
                    </form>
                </main>
            </div>
        </div>
    );
}
