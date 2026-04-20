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



// Menu Access Permission
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
