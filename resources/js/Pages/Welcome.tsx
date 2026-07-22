import ApplicationLogo from '@/Components/ApplicationLogo';
import { PageProps } from '@/types';
import { Head, Link } from '@inertiajs/react';

export default function Welcome({ auth }: PageProps) {
    return <>
        <Head title="Bienvenue" />
        <main className="min-h-screen bg-[#f6f4fb] px-5 py-6 text-[#302b45] sm:px-8">
            <div className="mx-auto flex min-h-[calc(100vh-3rem)] max-w-6xl flex-col">
                <header className="flex items-center justify-between">
                    <div className="flex items-center gap-3"><ApplicationLogo className="h-10 w-10" /><div><p className="font-semibold tracking-tight">MoneyMinder</p><p className="text-xs text-[#8b8699]">Mark 4</p></div></div>
                    <Link href={auth.user ? route('dashboard') : route('login')} className="rounded-xl border border-[#ddd6eb] bg-white px-4 py-2.5 text-sm font-semibold text-[#5a3da9] shadow-sm transition hover:bg-[#f3f0f8]">{auth.user ? 'Ouvrir mon espace' : 'Se connecter'}</Link>
                </header>

                <section className="grid flex-1 items-center gap-12 py-16 lg:grid-cols-[1.1fr_.9fr]">
                    <div><p className="mm-eyebrow">Ton argent, avec plus de sérénité</p><h1 className="mt-5 max-w-3xl text-5xl font-semibold leading-[1.06] tracking-[-.045em] sm:text-6xl">Décide avant de dépenser.</h1><p className="mt-6 max-w-xl text-lg leading-8 text-[#746f86]">MoneyMinder calcule ce que tu peux réellement utiliser, protège tes économies et t’aide à préparer tes projets sans fragiliser ton quotidien.</p><div className="mt-8 flex flex-wrap gap-3"><Link href={auth.user ? route('dashboard') : route('login')} className="rounded-xl bg-[#6d4cc7] px-5 py-3 font-semibold text-white shadow-sm transition hover:bg-[#5f42ae]">{auth.user ? 'Voir mon disponible' : 'Accéder à MoneyMinder'}</Link><a href="#principes" className="rounded-xl bg-white px-5 py-3 font-semibold text-[#5a3da9] ring-1 ring-[#e5dfef]">Découvrir</a></div></div>

                    <div className="mm-card p-6 sm:p-8"><p className="mm-eyebrow">La question essentielle</p><p className="mt-4 text-2xl font-semibold">Est-ce que je peux me le permettre ?</p><div className="mt-6 space-y-3"><div className="rounded-2xl bg-[#f3f0f8] p-4"><p className="text-sm text-[#746f86]">Disponible réel</p><p className="mt-1 text-3xl font-semibold">Après toutes tes obligations</p></div><div className="grid grid-cols-2 gap-3"><div className="rounded-2xl border border-[#ebe7f2] p-4"><p className="text-xs text-[#8b8699]">Chaque jour</p><p className="mt-1 font-semibold">Une limite claire</p></div><div className="rounded-2xl border border-[#ebe7f2] p-4"><p className="text-xs text-[#8b8699]">Tes projets</p><p className="mt-1 font-semibold">Un budget réservé</p></div></div><div className="rounded-2xl bg-emerald-50 p-4 text-sm leading-6 text-emerald-800">Tu sais pourquoi une dépense est possible, risquée ou déconseillée.</div></div></div>
                </section>

                <section id="principes" className="grid gap-4 border-t border-[#e8e4f0] py-8 sm:grid-cols-3">{[['Simple','Ton disponible utile, sans tableaux compliqués.'],['Préventif','Une décision chiffrée avant chaque achat important.'],['Personnel','Tes données restent séparées de celles de tes proches.']].map(([title,text])=><div key={title}><p className="font-semibold">{title}</p><p className="mt-1 text-sm leading-6 text-[#746f86]">{text}</p></div>)}</section>
            </div>
        </main>
    </>;
}
