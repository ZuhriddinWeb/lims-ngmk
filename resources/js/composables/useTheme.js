import { ref } from 'vue';

function initialTheme() {
    if (typeof document !== 'undefined') {
        const attr = document.documentElement.getAttribute('data-theme');
        if (attr) return attr;
    }
    return 'light';
}

const theme = ref(initialTheme());

export function useTheme() {
    function setTheme(mode) {
        theme.value = mode;
        document.documentElement.setAttribute('data-theme', mode);
        try {
            localStorage.setItem('lims_theme', mode);
        } catch (e) { /* ignore */ }
    }

    function toggle() {
        setTheme(theme.value === 'light' ? 'dark' : 'light');
    }

    return { theme, setTheme, toggle };
}
