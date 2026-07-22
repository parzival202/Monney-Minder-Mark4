import { InertiaLinkProps, Link } from '@inertiajs/react';

export default function NavLink({
    active = false,
    className = '',
    children,
    ...props
}: InertiaLinkProps & { active: boolean }) {
    return (
        <Link
            {...props}
            className={
                'inline-flex items-center rounded-xl px-3 py-2 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none ' +
                (active
                    ? 'bg-violet-50 text-violet-800'
                    : 'text-slate-500 hover:bg-slate-50 hover:text-slate-800') +
                className
            }
        >
            {children}
        </Link>
    );
}
