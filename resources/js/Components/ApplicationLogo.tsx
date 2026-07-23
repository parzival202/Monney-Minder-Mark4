import { SVGAttributes } from 'react';

export default function ApplicationLogo(props: SVGAttributes<SVGElement>) {
    return <svg {...props} viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" fill="none" aria-label="MoneyMinder">
        <defs><linearGradient id="mm-logo" x1="7" y1="5" x2="42" y2="44" gradientUnits="userSpaceOnUse"><stop stopColor="#8064D4"/><stop offset="1" stopColor="#5636B5"/></linearGradient></defs>
        <rect width="48" height="48" rx="15" fill="url(#mm-logo)" />
        <path d="M11.5 20.5h25a3 3 0 0 1 3 3v11a3 3 0 0 1-3 3h-25a3 3 0 0 1-3-3v-11a3 3 0 0 1 3-3Z" fill="white"/>
        <path d="M11.5 20.5 30 14.2a3 3 0 0 1 3.9 2.8v3.5" fill="#E8E1FA"/>
        <path d="M31.5 27h8v7h-8a3.5 3.5 0 1 1 0-7Z" fill="#D7CCF4"/>
        <circle cx="32" cy="30.5" r="1.35" fill="#6544BF"/>
        <path d="M14.5 17.9v-3.2M20 16v-5M25.5 14.2V8.5" stroke="#B9F3E6" strokeWidth="2.6" strokeLinecap="round"/>
        <path d="M16 25.5h7.5M19.75 23v12M16 29h6.2M16 32.5h6.2" stroke="#6544BF" strokeWidth="2" strokeLinecap="round"/>
    </svg>;
}
