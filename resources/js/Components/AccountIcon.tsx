import { SVGProps } from 'react';

type AccountLike = { name?: string; type?: string } | null | undefined;

const normalized = (value = '') => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

function WalletIcon(props: SVGProps<SVGSVGElement>) {
    return <svg {...props} viewBox="0 0 48 48" fill="none" aria-hidden="true"><rect width="48" height="48" rx="15" fill="#6D4CC7"/><path d="M10.5 19.5h25a3 3 0 0 1 3 3v13h-28a3 3 0 0 1-3-3v-10a3 3 0 0 1 3-3Z" fill="white"/><path d="m11 19.5 18-6a3 3 0 0 1 4 2.8v3.2" fill="#E8E1FA"/><path d="M30 26h9v7h-9a3.5 3.5 0 1 1 0-7Z" fill="#D6CAF3"/><circle cx="30.5" cy="29.5" r="1.3" fill="#6D4CC7"/><path d="M14 25h8M18 22.5v11M14.5 28.5h6.5M14.5 32h6.5" stroke="#6D4CC7" strokeWidth="2" strokeLinecap="round"/></svg>;
}

function GenericIcon({ type, ...props }: { type?: string } & SVGProps<SVGSVGElement>) {
    const symbol = type === 'cash' ? '₣' : type === 'savings' ? '↗' : type === 'mobile_money' ? '◉' : '▥';
    return <svg {...props} viewBox="0 0 48 48" aria-hidden="true"><rect width="48" height="48" rx="15" fill="#EEE9F8"/><text x="24" y="31" textAnchor="middle" fontSize="22" fontWeight="700" fill="#6544BF">{symbol}</text></svg>;
}

export default function AccountIcon({ account, className = 'h-11 w-11' }: { account?: AccountLike; className?: string }) {
    const name = normalized(account?.name);
    if (name.includes('wave')) return <img src="/account-icons/wave.png" alt="Wave" className={`${className} rounded-xl object-cover`} />;
    if (name.includes('orange money') || name === 'orange' || name.includes('orangemoney')) return <img src="/account-icons/orange-money.png" alt="Orange Money" className={`${className} rounded-xl object-cover`} />;
    if (/(^|\s)sib($|\s)/.test(name) || name.includes('societe ivoirienne de banque')) return <img src="/account-icons/sib.jpg" alt="SIB" className={`${className} rounded-xl object-cover`} />;
    if (name.includes('principal') || account?.type === 'main') return <WalletIcon className={className} />;
    return <GenericIcon type={account?.type} className={className} />;
}
