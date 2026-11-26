<script setup>
/**
 * Umumiy karkas — zavod LIMS'i bilan bir xil: chapda yon panel (laboratoriyalar va holati), tepada satr
 * (sahifa yo'li, jonli soat, til, mavzu, foydalanuvchi). Kontent butun kenglikni egallaydi.
 * mode: 'public' — kombinat ko'rinishi; 'admin' — boshqaruv.
 */
import { computed, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import Icon from '@/Components/Icon.vue';
import { useNow } from '@/composables/useNow';
import { useTheme } from '@/composables/useTheme';
import { useLang } from '@/composables/useLang';
import { clockTime, longDate } from '@/lib/charts';

const props = defineProps({
    mode: { type: String, default: 'public' },
    crumbs: { type: Array, default: () => [] }, // [{ label, href? }]
    activeLab: { type: Number, default: null },
    live: { type: Boolean, default: false },
});

const { t, locale } = useI18n();
const page = usePage();
const now = useNow(1000);
const { theme, toggle } = useTheme();
const { setLang, locales } = useLang();

const user = computed(() => page.props.auth?.user ?? null);
const navLabs = computed(() => page.props.nav?.labs ?? []);
const onlineN = computed(() => navLabs.value.filter((l) => l.state === 'online').length);
const path = computed(() => (page.url || '/').split('?')[0]);
const labName = (l) => l.name?.[locale.value] || l.name?.ru || '';
const initials = computed(() => (user.value?.name ?? '').trim().split(/\s+/).slice(0, 2).map((p) => p[0]?.toUpperCase() ?? '').join('') || 'A');

const collapsed = ref(false);
const mobileOpen = ref(false);
onMounted(() => { try { collapsed.value = localStorage.getItem('portal_sb') === '1'; } catch (e) { /* */ } });
function toggleCollapse() {
    collapsed.value = !collapsed.value;
    try { localStorage.setItem('portal_sb', collapsed.value ? '1' : '0'); } catch (e) { /* */ }
}
watch(path, () => { mobileOpen.value = false; });

const langOpen = ref(false);
const userOpen = ref(false);
const LANG_LABEL = { uz: "O'zbekcha", ru: 'Русский', en: 'English' };
function pickLang(l) { setLang(l); langOpen.value = false; }
function logout() { userOpen.value = false; router.post('/admin/logout'); }

const flashSuccess = computed(() => page.props.flash?.success);
const flashError = computed(() => page.props.flash?.error);
</script>

<template>
    <div :class="{ collapsed, 'sb-open': mobileOpen }">
        <div class="sb-shade" @click="mobileOpen = false" />

        <aside class="sb" :aria-label="t('shell.nav')">
            <Link :href="mode === 'admin' ? '/admin/labs' : '/'" class="sb-brand">
                <img src="/images/ngmk.png" alt="НГМК" />
                <div class="tx">
                    <div class="t">{{ t('shell.app') }}</div>
                    <div class="s">{{ mode === 'admin' ? t('shell.adminSub') : t('shell.sub') }}</div>
                </div>
            </Link>

            <nav class="sb-nav">
                <template v-if="mode === 'admin'">
                    <Link href="/admin/labs" class="sb-item" :class="{ active: path === '/admin/labs' || path.endsWith('/edit') }" :title="t('admin.title')">
                        <Icon name="network" :size="17" /><span class="lbl">{{ t('admin.title') }}</span>
                    </Link>
                    <Link href="/admin/labs/create" class="sb-item" :class="{ active: path === '/admin/labs/create' }" :title="t('admin.add')">
                        <Icon name="plus" :size="17" /><span class="lbl">{{ t('admin.add') }}</span>
                    </Link>
                    <div class="sb-label">{{ t('shell.site') }}</div>
                    <Link href="/" class="sb-item" :title="t('shell.overview')">
                        <Icon name="grid" :size="17" /><span class="lbl">{{ t('shell.overview') }}</span>
                    </Link>
                </template>

                <template v-else>
                    <Link href="/" class="sb-item" :class="{ active: path === '/' }" :title="t('shell.overview')">
                        <Icon name="grid" :size="17" /><span class="lbl">{{ t('shell.overview') }}</span>
                    </Link>
                </template>

                <div class="sb-label">{{ t('sections.labs') }} <span v-if="navLabs.length" class="cnt tnum">{{ onlineN }}/{{ navLabs.length }}</span></div>
                <Link
                    v-for="l in navLabs" :key="l.id" :href="`/labs/${l.id}`"
                    class="sb-item sb-lab" :class="{ active: activeLab === l.id }" :title="`${l.code} — ${labName(l)} · ${t('state.' + l.state)}`"
                >
                    <span class="dot" :class="l.state" />
                    <span class="code">{{ l.code }}</span>
                    <span class="lbl">{{ labName(l) }}</span>
                    <span v-if="l.demo" class="demo">DEMO</span>
                </Link>
            </nav>

            <div class="sb-foot">
                <Link v-if="mode !== 'admin'" :href="user ? '/admin/labs' : '/admin/login'" class="sb-item" :title="t('shell.admin')">
                    <Icon name="shield" :size="17" /><span class="lbl">{{ t('shell.admin') }}</span>
                </Link>
                <button class="sb-item sb-collapse" type="button" :title="t('shell.collapse')" @click="toggleCollapse">
                    <Icon name="chevrons-left" :size="17" :style="collapsed ? 'transform:rotate(180deg)' : ''" /><span class="lbl">{{ t('shell.collapse') }}</span>
                </button>
            </div>
        </aside>

        <div class="shell">
            <header class="hd">
                <button class="iconbtn hd-menu" type="button" :aria-label="t('shell.nav')" @click="mobileOpen = true"><Icon name="menu" :size="19" /></button>
                <nav class="crumbs" aria-label="breadcrumb">
                    <template v-for="(c, i) in crumbs" :key="i">
                        <Icon v-if="i" name="chevron-right" :size="14" />
                        <Link v-if="c.href && i < crumbs.length - 1" :href="c.href">{{ c.label }}</Link>
                        <b v-else>{{ c.label }}</b>
                    </template>
                </nav>
                <div class="grow" />

                <div class="clock" :title="t('nav.live')">
                    <span class="dot" :class="live ? 'online' : 'soon'" />
                    <span class="ct">{{ longDate(now, locale) }}</span>
                    <b>{{ clockTime(now) }}</b>
                </div>

                <div class="menu">
                    <button class="btn btn-ghost btn-sm" type="button" :aria-label="t('shell.lang')" @click="langOpen = !langOpen">
                        <Icon name="languages" :size="17" /><span style="font-weight:600">{{ locale.toUpperCase() }}</span>
                    </button>
                    <template v-if="langOpen">
                        <div class="menu-shade" @click="langOpen = false" />
                        <div class="menu-pop">
                            <button v-for="l in locales" :key="l" type="button" :class="{ on: l === locale }" @click="pickLang(l)">{{ LANG_LABEL[l] }} <span class="k">{{ l.toUpperCase() }}</span></button>
                        </div>
                    </template>
                </div>

                <button class="iconbtn" type="button" :title="t('nav.theme')" :aria-label="t('nav.theme')" @click="toggle">
                    <Icon :name="theme === 'light' ? 'moon' : 'sun'" :size="18" />
                </button>

                <div v-if="user" class="menu">
                    <button class="userbtn" type="button" @click="userOpen = !userOpen">
                        <span class="avatar">{{ initials }}</span><span class="nm">{{ user.name }}</span><Icon name="chevron-down" :size="14" style="color:var(--faint)" />
                    </button>
                    <template v-if="userOpen">
                        <div class="menu-shade" @click="userOpen = false" />
                        <div class="menu-pop" style="min-width:210px">
                            <div style="padding:8px 10px 10px;border-bottom:1px solid var(--line);margin-bottom:4px">
                                <div style="font-weight:500">{{ user.name }}</div>
                                <div style="font-size:12px;color:var(--muted)">{{ user.email }}</div>
                            </div>
                            <Link v-if="mode !== 'admin'" href="/admin/labs"><span style="display:flex;gap:10px;align-items:center"><Icon name="shield" :size="16" />{{ t('shell.admin') }}</span></Link>
                            <Link v-else href="/"><span style="display:flex;gap:10px;align-items:center"><Icon name="grid" :size="16" />{{ t('shell.overview') }}</span></Link>
                            <button type="button" style="color:var(--crit)" @click="logout"><span style="display:flex;gap:10px;align-items:center"><Icon name="logout" :size="16" />{{ t('admin.logout') }}</span></button>
                        </div>
                    </template>
                </div>
                <Link v-else href="/admin/login" class="btn btn-outline btn-sm"><Icon name="user" :size="15" /><span class="nm">{{ t('shell.login') }}</span></Link>
            </header>

            <main class="content">
                <div v-if="flashSuccess" class="flash success">{{ flashSuccess }}</div>
                <div v-if="flashError" class="flash error">{{ flashError }}</div>
                <slot />
            </main>

            <footer class="foot">
                <span>{{ t('footer.org') }}</span>
                <span>{{ t('footer.sub') }}</span>
                <span class="grow" />
                <span>{{ t('footer.note') }}</span>
                <span>v3</span>
            </footer>
        </div>
    </div>
</template>
