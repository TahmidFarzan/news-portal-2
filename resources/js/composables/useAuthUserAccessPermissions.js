export const getUserRole = (user) => user?.user_role?.name?.toLowerCase()

export const hasUserRole = (user, userRoles) => {
    if (!user) return false

    const currentUserRole = getUserRole(user)
    if (!currentUserRole) return false

    const userRoleList = (Array.isArray(userRoles) ? userRoles : [userRoles])
        .map(r => r.toLowerCase())

    return userRoleList.includes(currentUserRole)
}

// User Access Permission
export const canCreateUser = (authUser) => {
    if (!authUser) return false

    const authUserUserRole = getUserRole(authUser)

    if (authUserUserRole === 'admin') return true

    return false
}

export const canEditUser = (authUser, targetUser) => {
    if (!authUser, !targetUser) return false

    const authUserUserRole = getUserRole(authUser)
    const targetUserUserRole = getUserRole(targetUser)

    if (authUserUserRole === 'admin') return true

    if (
        authUserUserRole === 'news desk' &&
        authUser.id === targetUser?.id
    ) {
        return true
    }

    return false
}

export const canDeleteUser = (authUser, targetUser) => {
    if (!authUser, !targetUser) return false

    if (targetUser?.is_default) return false

    const authUserUserRole = getUserRole(authUser)
    const targetUserUserRole = getUserRole(targetUser)

    if (authUserUserRole === 'admin') return true

    if ((authUserUserRole === 'news desk') && (authUser.id === targetUser?.id)) {
        return true
    }

    return false
}

export const canActiveInactiveUser = (authUser, targetUser) => {
    if (!authUser, !targetUser) return false

    if (targetUser?.is_default) return false

    const authUserUserRole = getUserRole(authUser)
    const targetUserUserRole = getUserRole(targetUser)

    if (authUserUserRole === 'admin') return true


    if ((authUserUserRole === 'news desk') && (authUser.id === targetUser?.id)) {
        return true
    }

    return false
}

export const canDeleteMedia = (authUser, media) => {
    if (!authUser, !media) return false

    const authUserUserRole = getUserRole(authUser)

    if (authUserUserRole === 'admin') return true

    if (authUserUserRole === 'news desk') return true

    return false
}

// Language Access Permission
export const canCreateLanguage = (authUser) => {
    if (!authUser) return false

    const authUserUserRole = getUserRole(authUser)

    if (authUserUserRole === 'admin') return true

    return false
}

export const canEditLanguage = (authUser, language) => {
    if (!authUser, !language) return false

    const authUserUserRole = getUserRole(authUser)

    if (authUserUserRole === 'admin') return true

    if (
        authUserUserRole === 'news desk' &&
        authUser.id === language?.created_by_id
    ) {
        return true
    }

    return false
}

export const canDeleteLanguage = (authUser, language) => {
    if (!authUser, !language) return false


    const authUserUserRole = getUserRole(authUser)
    const languageUserRole = getUserRole(language)

    if (authUserUserRole === 'admin') return true

    if ((authUserUserRole === 'news desk') && (authUser.id === language?.created_by_id)) {
        return true
    }

    return false
}

// Category Access Permission
export const canCreateCategory = (authUser) => {
    if (!authUser) return false

    const authUserUserRole = getUserRole(authUser)

    if (authUserUserRole === 'admin') return true

    return false
}

export const canEditCategory = (authUser, category) => {
    if (!authUser, !category) return false

    const authUserUserRole = getUserRole(authUser)

    if (authUserUserRole === 'admin') return true

    if (
        authUserUserRole === 'news desk' &&
        authUser.id === category?.created_by_id
    ) {
        return true
    }

    return false
}

export const canDeleteCategory = (authUser, category) => {
    if (!authUser, !category) return false


    const authUserUserRole = getUserRole(authUser)
    const categoryUserRole = getUserRole(category)

    if (authUserUserRole === 'admin') return true

    if ((authUserUserRole === 'news desk') && (authUser.id === category?.created_by_id)) {
        return true
    }

    return false
}


// Layout Menu Access Permission
export const canAccessActivityLogMenu = (authUser) => {
    if (!authUser) return false

    const authUserUserRole = getUserRole(authUser)

    if (authUserUserRole === 'admin') return true

    return false
}

export const canAccessUserManagementMenu = (authUser) => {
    if (!authUser) return false

    const authUserUserRole = getUserRole(authUser)

    if (authUserUserRole === 'admin') return true

    if (authUserUserRole === 'news desk') return true

    return false
}


export const canAccessNewsManagementMenu = (authUser) => {
    if (!authUser) return false

    const authUserUserRole = getUserRole(authUser)

    if (authUserUserRole === 'admin') return true

    if (authUserUserRole === 'news desk') return true

    return false
}
