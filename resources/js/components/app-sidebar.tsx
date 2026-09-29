import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    BookIcon,
    CalendarDays,
    Database,
    ExternalLink,
    LayoutGrid,
    MessageCircle,
    ScrollText,
    Settings,
    ShoppingCart,
    Sparkles,
    UsersIcon,
    UsersRound,
    WalletIcon,
    Warehouse,
} from 'lucide-react';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import Wordmark from '@/components/site/wordmark';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePoll } from '@/hooks/use-poll';
import { useCan } from '@/lib/admin';
import { dashboard, home } from '@/routes';
import appointments from '@/routes/admin/appointments';
import accounting from '@/routes/admin/accounting';
import audit from '@/routes/admin/audit';
import catalog from '@/routes/admin/catalog';
import chat from '@/routes/admin/chat';
import hr from '@/routes/admin/hr';
import inventory from '@/routes/admin/inventory';
import payroll from '@/routes/admin/payroll';
import leads from '@/routes/admin/leads';
import pos from '@/routes/admin/pos';
import settings from '@/routes/admin/settings/clinic';
import setup from '@/routes/admin/setup';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const { clinic, build } = usePage().props;
    const can = useCan();
    const [waiting, setWaiting] = useState(0);

    // The badge is the whole point of a chat menu item: somebody types a
    // question on the website while the desk is somewhere else in the
    // system, and the menu should say so. Polled slowly and on every screen,
    // not just the inbox, which is where the count is actually needed.
    usePoll<{ unread: number }>(
        can('leads.view') ? chat.poll().url : null,
        (data) => setWaiting(data.unread),
        20000,
    );

    // Ordered the way the day actually runs rather than by module: take the
    // bookings, work through them, sell what is on the shelf, then pay for it.
    // Anything used once a week sits below anything used every hour.
    const mainNavItems: NavItem[] = [
        { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
        ...(can('appointments.view')
            ? [
                  {
                      title: 'Appointments',
                      href: appointments.index(),
                      icon: CalendarDays,
                  },
              ]
            : []),
        ...(can('leads.view')
            ? [
                  { title: 'Leads', href: leads.index(), icon: UsersRound },
                  {
                      title: 'Chat',
                      href: chat.index(),
                      icon: MessageCircle,
                      badge: waiting || null,
                  },
              ]
            : []),
        ...(can('inventory.view')
            ? [
                  {
                      // Opens on the treatments tab, because that is what a
                      // clinic owner comes here to look at. It was not in the
                      // menu at all before, and the whole catalogue was only
                      // reachable by typing a url.
                      title: 'Catalogue',
                      href: catalog.index({ query: { tab: 'services' } }),
                      icon: Sparkles,
                  },
              ]
            : []),
        ...(can('pos.view')
            ? [
                  {
                      title: 'Point of sale',
                      href: pos.index(),
                      icon: ShoppingCart,
                  },
              ]
            : []),
        ...(can('inventory.view')
            ? [
                  {
                      title: 'Inventory',
                      href: inventory.index(),
                      icon: Warehouse,
                  },
              ]
            : []),
        ...(can('hr.view')
            ? [
                  {
                      title: 'Staff',
                      href: hr.index(),
                      icon: UsersIcon,
                  },
                  {
                      title: 'Payroll',
                      href: payroll.index(),
                      icon: WalletIcon,
                  },
              ]
            : []),
        ...(can('accounting.view')
            ? [
                  {
                      title: 'Accounting',
                      href: accounting.index(),
                      icon: BookIcon,
                  },
              ]
            : []),
        ...(can('settings.view')
            ? [
                  {
                      title: 'Audit log',
                      href: audit.index(),
                      icon: ScrollText,
                  },
              ]
            : []),
        ...(can('settings.edit')
            ? [
                  {
                      title: 'Clinic settings',
                      href: settings.index(),
                      icon: Settings,
                  },
              ]
            : []),
    ];

    // Demonstration tools, below the fold and out of a real clinic's way.
    const utilityNavItems: NavItem[] = can('settings.edit')
        ? [
              {
                  title: 'Clinic data',
                  href: setup.index(),
                  icon: Database,
              },
          ]
        : [];

    const footerNavItems: NavItem[] = [
        { title: 'View website', href: home(), icon: ExternalLink },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link
                                href={dashboard()}
                                prefetch
                                aria-label={`${clinic.short_name} dashboard`}
                            >
                                <Wordmark
                                    descriptor="Clinic desk"
                                    markClassName="size-8"
                                    className="group-data-[collapsible=icon]:[&>span:last-child]:hidden"
                                />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                {utilityNavItems.length > 0 && (
                    <div className="mt-6 border-t border-sidebar-border pt-4">
                        <NavMain items={utilityNavItems} />
                    </div>
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <p
                    className="px-2 pb-1 text-center text-[10px] tracking-wider text-muted-foreground/60 uppercase group-data-[collapsible=icon]:hidden"
                    title="Which compiled build this screen is running"
                >
                    build {build.slice(0, 7)}
                </p>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
