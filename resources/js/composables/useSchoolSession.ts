import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { SchoolSessionInfo } from '@/types';

export function useSchoolSession() {
    const page = usePage();

    const sessionInfo = computed<SchoolSessionInfo | undefined>(
        () => page.props.school_session as SchoolSessionInfo | undefined
    );

    const currentSession = computed(() => sessionInfo.value?.view_session || 'teori');
    const isTeori = computed(() => currentSession.value === 'teori');
    const isBengkel = computed(() => currentSession.value === 'bengkel');

    return {
        sessionInfo,
        currentSession,
        isTeori,
        isBengkel,
    };
}
