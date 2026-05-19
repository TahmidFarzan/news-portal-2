const userRoles = {
    Admin: 'Admin',
    NewsDesk: 'News Desk',
}

// Normalize user role once
export const getUserRoleName = (user) =>
    user?.user_role?.name?.toLowerCase()

// Helper
const isAdmin = (role) =>
    role === userRoles.Admin.toLowerCase()

const isNewsDesk = (role) =>
    role === userRoles.NewsDesk.toLowerCase()

// ================= USER =================

export const canCreateUser = (authUser) => {
    if (!authUser) return false

    const role = getUserRoleName(authUser)
    return isAdmin(role)
}

export const canEditUser = (authUser, targetUser) => {
    if (!authUser || !targetUser) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role)) return true

    return isNewsDesk(role) && authUser.id === targetUser.id
}

export const canDeleteUser = (authUser, targetUser) => {
    if (!authUser || !targetUser) return false
    if (targetUser?.is_default) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role)) return true

    return isNewsDesk(role) && authUser.id === targetUser.id
}

export const canActiveInactiveUser = (authUser, targetUser) => {
    if (!authUser || !targetUser) return false
    if (targetUser?.is_default) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role)) return true

    return isNewsDesk(role) && authUser.id === targetUser.id
}

export const canDeleteMedia = (authUser, media) => {
    if (!authUser || !media) return false

    const role = getUserRoleName(authUser)

    return isAdmin(role) || isNewsDesk(role)
}

// ================= LANGUAGE =================

export const canCreateLanguage = (authUser) => {
    if (!authUser) return false
    return isAdmin(getUserRoleName(authUser))
}

export const canEditLanguage = (authUser, language) => {
    if (!authUser || !language) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role)) return true

    return isNewsDesk(role) && authUser.id === language.created_by_id
}

export const canDeleteLanguage = (authUser, language) => {
    if (!authUser || !language) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role)) return true

    return isNewsDesk(role) && authUser.id === language.created_by_id
}

// ================= CATEGORY =================

export const canCreateCategory = (authUser) =>
    authUser && isAdmin(getUserRoleName(authUser))

export const canEditCategory = (authUser, category) => {
    if (!authUser || !category) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role)) return true

    return isNewsDesk(role) && authUser.id === category.created_by_id
}

export const canDeleteCategory = (authUser, category) => {
    if (!authUser || !category) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role)) return true

    return isNewsDesk(role) && authUser.id === category.created_by_id
}


// ================= TAG =================

export const canCreateTag = (authUser) =>
    authUser && isAdmin(getUserRoleName(authUser))

export const canEditTag = (authUser, tag) => {
    if (!authUser || !tag) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role)) return true

    return isNewsDesk(role) && authUser.id === tag.created_by_id
}

export const canDeleteTag = (authUser, tag) => {
    if (!authUser || !tag) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role)) return true

    return isNewsDesk(role) && authUser.id === tag.created_by_id
}

// ================= TREND =================

export const canCreateTrend = (authUser) =>
    authUser && isAdmin(getUserRoleName(authUser))

export const canEditTrend = (authUser, trend) => {
    if (!authUser || !trend) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role)) return true

    return isNewsDesk(role) && authUser.id === trend.created_by_id
}

export const canDeleteTrend = (authUser, trend) => {
    if (!authUser || !trend) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role)) return true

    return isNewsDesk(role) && authUser.id === trend.created_by_id
}

// ================= LOCATION =================

export const canCreateLocation = (authUser) =>
    authUser && isAdmin(getUserRoleName(authUser))

export const canEditLocation = (authUser, location) => {
    if (!authUser || !location) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role)) return true

    return isNewsDesk(role) && authUser.id === location.created_by_id
}

export const canDeleteLocation = (authUser, location) => {
    if (!authUser || !location) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role)) return true

    return isNewsDesk(role) && authUser.id === location.created_by_id
}

// ================= EVENT =================

export const canCreateEvent = (authUser) =>
    authUser && isAdmin(getUserRoleName(authUser))

export const canEditEvent = (authUser, event) => {
    if (!authUser || !event) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role)) return true

    return isNewsDesk(role) && authUser.id === event.created_by_id
}

export const canDeleteEvent = (authUser, event) => {
    if (!authUser || !event) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role)) return true

    return isNewsDesk(role) && authUser.id === event.created_by_id
}

// ================= CONTRIBUTOR =================

export const canCreateContributor = (authUser) =>
    authUser && isAdmin(getUserRoleName(authUser))

export const canEditContributor = (authUser, contributor) => {
    if (!authUser || !contributor) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role)) return true

    return isNewsDesk(role) && authUser.id === contributor.created_by_id
}

export const canDeleteContributor = (authUser, contributor) => {
    if (!authUser || !contributor) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role)) return true

    return isNewsDesk(role) && authUser.id === contributor.created_by_id
}

// ================= NEWS =================

export const canCreateNews = (authUser) =>
    authUser && isAdmin(getUserRoleName(authUser))

export const canEditNews = (authUser, news) => {
    if (!authUser || !news) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role)) return true

    return isNewsDesk(role)
}

export const canDeleteNews = (authUser, news) => {
    if (!authUser || !news) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role) && news.is_published) return true

    return isNewsDesk(role) && news.is_published
}

export const canRestoreNews = (authUser, news) => {
    if (!authUser || !news) return false


    const role = getUserRoleName(authUser)

    if (isAdmin(role) && (news.is_published == false)) return true

    return isNewsDesk(role) && (news.is_published == false)
}

// ================= MENU =================

export const canCreateMenu = (authUser) =>
    authUser && isAdmin(getUserRoleName(authUser))

export const canEditMenu = (authUser, menu) => {
    if (!authUser || !menu) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role)) return true

    return false
}

export const canDeleteMenu = (authUser, menu) => {
    if (!authUser || !menu) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role)) return true

    return false
}


// ================= MENU ITEM =================

export const canCreateMenuItem = (authUser) =>
    authUser && isAdmin(getUserRoleName(authUser))

export const canEditMenuItem = (authUser, menu) => {
    if (!authUser || !menu) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role)) return true

    return false
}

export const canDeleteMenuItem = (authUser, menu) => {
    if (!authUser || !menu) return false

    const role = getUserRoleName(authUser)

    if (isAdmin(role)) return true

    return false
}

// ================= MENU =================

export const canAccessActivityLogMenu = (authUser) =>
    authUser && isAdmin(getUserRoleName(authUser))

export const canAccessUserManagementMenu = (authUser) => {
    if (!authUser) return false

    const role = getUserRoleName(authUser)

    return isAdmin(role) || isNewsDesk(role)
}

export const canAccessNewsAttributesMenu = (authUser) => {
    if (!authUser) return false

    const role = getUserRoleName(authUser)

    return isAdmin(role) || isNewsDesk(role)
}

export const canAccessNewsMenu = (authUser) => {
    if (!authUser) return false

    const role = getUserRoleName(authUser)

    return isAdmin(role) || isNewsDesk(role)
}

export const canAccessMenuMenu = (authUser) => {
    if (!authUser) return false

    const role = getUserRoleName(authUser)

    return isAdmin(role)
}
