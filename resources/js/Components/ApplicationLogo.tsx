import { SVGAttributes } from 'react';

export default function ApplicationLogo(props: SVGAttributes<SVGElement>) {
    return <svg {...props} viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg" fill="none">
        <rect width="48" height="48" rx="15" fill="#6D4CC7" />
        <path d="M13 31V18.5c0-1.1.9-2 2-2h2.2l6.8 8.2 6.8-8.2H33c1.1 0 2 .9 2 2V31" stroke="white" strokeWidth="3.2" strokeLinecap="round" strokeLinejoin="round" />
        <path d="M18 31h12" stroke="#DCCFFB" strokeWidth="3.2" strokeLinecap="round" />
    </svg>;
}
