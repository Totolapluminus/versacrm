const TICKET_TYPE_LABELS = {
    login: 'Проблемы со входом',
    access: 'Проблемы с разделами',
    profile_update: 'Проблемы с профилем',
    notifications: 'Проблемы с уведомлениями',
    bug: 'Баг в кабинете',
    other: 'Другие проблемы',
}

const TICKET_DOMAIN_LABELS = {
    my: 'my.melsu',
    lms: 'lms.melsu',
    rasp: 'rasp.melsu',
    other: 'Другое',
}

export function useChatLabels() {
    const ticketTypeLabel = (key) => {
        if (!key) return '—'
        return TICKET_TYPE_LABELS[key] || key
    }

    const ticketDomainLabel = (key) => {
        if (!key) return '—'
        return TICKET_DOMAIN_LABELS[key] || key
    }

    const getInitials = (chat) => {
        const name = chat.telegram_user?.username || chat.telegram_user?.first_name || ''
        const trimmed = name.trim()

        if (!trimmed) return '?'

        const letters = trimmed.replace(/[^A-Za-zА-Яа-яЁё0-9]/g, '')
        return letters.slice(0, 1).toUpperCase()
    }

    return {
        ticketTypeLabel,
        ticketDomainLabel,
        getInitials,
    }
}
