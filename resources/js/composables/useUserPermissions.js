
import { fetchFromApi } from '@/composables/useApiClient'
import { apiCacheKey, apiCacheTTL, useApiCache } from '@/composables/useApiCache'

const { clearByPrefix } = useApiCache()

export const groups = {
    BreakingNews: 'Breaking news',
    Category: 'Category',
    Contributor: 'Contributor',
    Event: 'Event',
    GoogleAdsence: 'Google adsence',
    Location: 'Location',
    Menu: 'Menu',
    MenuItem: 'Menu item',
    News: 'News',
    Page: 'Page',
    Tag: 'Tag',
    Theme: 'Theme',
    Trend: 'Trend',
    User: 'User',
    Survey: 'Survey',
    SurveyQuestion: 'Survey question',
}

export const access = {
    ViewAny: 'View any',
    View: 'View',
    Create: 'Create',
    Update: 'Update',
    Delete: 'Delete',
    Restore: 'Restore',
    ForceDelete: 'Force delete',
}

export const getPermissions = async (authUser) => {
    if (!authUser) {
        return []
    }

    let permissions = authUser.user_permissions

    if (!Array.isArray(permissions)) {
        const apiUrl = route('search.user', { slugOrId: authUser.id, })
        const user = await fetchFromApi(
            apiUrl,
            {},
            {
                key: `${apiCacheKey.API_USER}:${authUser.id}`,
                ttl: apiCacheTTL.API_USER,
            }
        )

        permissions = user?.user_permissions || []
    }

    return permissions
}

export const clearPermissionCache = (userId) => {
    if (userId) {
        clearByPrefix(`${apiCacheKey.API_USER}:${userId}`)
        return
    }

    clearByPrefix(`${apiCacheKey.API_USER}:`)
}

export const hasPermission = async (authUser, module, permissionAccess) => {
    if (!authUser) {
        return false
    }

    if ( authUser.is_super_admin ) {
        return true
    }

    const permissions = await getPermissions( authUser )

    return permissions.some(
        permission =>
            permission.module ===
            module &&
            permission.access ===
            permissionAccess
    )
}

export const canAccessUser = async (authUser) => hasPermission(authUser, groups.User, access.ViewAny)
export const canCreateUser = async (authUser) => hasPermission(authUser, groups.User, access.Create)
export const canUpdateUser = async (authUser, user) => hasPermission(authUser, groups.User, access.Update)
export const canActiveInactiveUser = async (authUser, user) => {
    if (user?.is_default) {
        return false
    }
    if (user?.deleted_at) {
        return await hasPermission(
            authUser,
            groups.User,
            access.Restore
        )
    }
    else {
        return await hasPermission(
            authUser,
            groups.User,
            access.Delete
        )
    }
}
export const canDeleteUser = async (authUser, user) => {
    if (user?.is_default) {
        return false
    }
    return await hasPermission(
        authUser,
        groups.User,
        access.ForceDelete
    )
}

export const canAccessCategory = async (authUser) => hasPermission(authUser, groups.Category, access.ViewAny)
export const canCreateCategory = async (authUser) => hasPermission(authUser, groups.Category, access.Create)
export const canUpdateCategory = async (authUser, category) => hasPermission(authUser, groups.Category, access.Update)
export const canDeleteCategory = async (authUser, category) => hasPermission(authUser, groups.Category, access.Delete)

export const canAccessTag = async (authUser) => hasPermission(authUser, groups.Tag, access.ViewAny)
export const canCreateTag = async (authUser, tag) => hasPermission(authUser, groups.Tag, access.Create)
export const canUpdateTag = async (authUser, tag) => hasPermission(authUser, groups.Tag, access.Update)
export const canDeleteTag = async (authUser, tag) => hasPermission(authUser, groups.Tag, access.Delete)

export const canAccessTrend = async (authUser) => hasPermission(authUser, groups.Trend, access.ViewAny)
export const canCreateTrend = async (authUser) => hasPermission(authUser, groups.Trend, access.Create)
export const canUpdateTrend = async (authUser, trend) => hasPermission(authUser, groups.Trend, access.Update)
export const canDeleteTrend = async (authUser, trend) => hasPermission(authUser, groups.Trend, access.Delete)

export const canAccessLocation = async (authUser) => hasPermission(authUser, groups.Location, access.ViewAny)
export const canCreateLocation = async (authUser) => hasPermission(authUser, groups.Location, access.Create)
export const canUpdateLocation = async (authUser, location) => hasPermission(authUser, groups.Location, access.Update)
export const canDeleteLocation = async (authUser, location) => hasPermission(authUser, groups.Location, access.Delete)

export const canAccessEvent = async (authUser) => hasPermission(authUser, groups.Event, access.ViewAny)
export const canCreateEvent = async (authUser) => hasPermission(authUser, groups.Event, access.Create)
export const canUpdateEvent = async (authUser, event) => hasPermission(authUser, groups.Event, access.Update)
export const canDeleteEvent = async (authUser, event) => hasPermission(authUser, groups.Event, access.Delete)

export const canAccessContributor = async (authUser) => hasPermission(authUser, groups.Contributor, access.ViewAny)
export const canCreateContributor = async (authUser) => hasPermission(authUser, groups.Contributor, access.Create)
export const canUpdateContributor = async (authUser, contributor) => hasPermission(authUser, groups.Contributor, access.Update)
export const canDeleteContributor = async (authUser, contributor) => hasPermission(authUser, groups.Contributor, access.Delete)

export const canAccessNews = async (authUser) => hasPermission(authUser, groups.News, access.ViewAny)
export const canCreateNews = async (authUser) => hasPermission(authUser, groups.News, access.Create)
export const canUpdateNews = async (authUser, news) => hasPermission(authUser, groups.News, access.Update)
export const canDeleteNews = async (authUser, news) => hasPermission(authUser, groups.News, access.Delete)
export const canRestoreNews = async (authUser, news) => hasPermission(authUser, groups.News, access.Restore)

export const canAccessBreakingNews = async (authUser) => hasPermission(authUser, groups.BreakingNews, access.ViewAny)
export const canCreateBreakingNews = async (authUser) => hasPermission(authUser, groups.BreakingNews, access.Create)
export const canUpdateBreakingNews = async (authUser, breakingNews) => hasPermission(authUser, groups.BreakingNews, access.Update)
export const canTrashBreakingNews = async (authUser, breakingNews) => hasPermission(authUser, groups.BreakingNews, access.Delete)
export const canRestoreBreakingNews = async (authUser, breakingNews) => hasPermission(authUser, groups.BreakingNews, access.Restore)
export const canDeleteBreakingNews = async (authUser, breakingNews) => hasPermission(authUser, groups.BreakingNews, access.ForceDelete)

export const canAccessPage = async (authUser) => hasPermission(authUser, groups.Page, access.ViewAny)
export const canCreatePage = async (authUser) => hasPermission(authUser, groups.Page, access.Create)
export const canUpdatePage = async (authUser, page) => hasPermission(authUser, groups.Page, access.Update)
export const canTrashPage = async (authUser, page) => {
    if (page?.is_default) {
        return false
    }
    return await hasPermission(authUser, groups.Page, access.Delete)
}
export const canRestorePage = async (authUser, page) => {
    if (page?.is_default) {
        return false
    }
    return await hasPermission(authUser, groups.Page, access.Restore)
}
export const canDeletePage = async (authUser, page) => {
    if (page?.is_default) {
        return false
    }
    return await hasPermission(authUser, groups.Page, access.ForceDelete)
}

export const canAccessMenu = async (authUser) => hasPermission(authUser, groups.Menu, access.ViewAny)
export const canCreateMenu = async (authUser) => hasPermission(authUser, groups.Menu, access.Create)
export const canUpdateMenu = async (authUser, menu) => hasPermission(authUser, groups.Menu, access.Update)
export const canDeleteMenu = async (authUser, menu) => hasPermission(authUser, groups.Menu, access.Delete)

export const canAccessMenuItem = async (authUser) => hasPermission(authUser, groups.MenuItem, access.ViewAny)
export const canCreateMenuItem = async (authUser) => hasPermission(authUser, groups.MenuItem, access.Create)
export const canUpdateMenuItem = async (authUser, menuItem) => hasPermission(authUser, groups.MenuItem, access.Update)
export const canDeleteMenuItem = async (authUser, menuItem) => hasPermission(authUser, groups.MenuItem, access.Delete)

export const canAccessTheme = async (authUser) => hasPermission(authUser, groups.Theme, access.ViewAny)
export const canUpdateTheme = async (authUser, theme) => hasPermission(authUser, groups.Theme, access.Update)

export const canAccessGoogleAdsence = async (authUser) => hasPermission(authUser, groups.GoogleAdsence, access.ViewAny)
export const canCreateGoogleAdsence = async (authUser) => hasPermission(authUser, groups.GoogleAdsence, access.Create)
export const canUpdateGoogleAdsence = async (authUser, googleAdsence) => hasPermission(authUser, groups.GoogleAdsence, access.Update)
export const canDeleteGoogleAdsence = async (authUser, googleAdsence) => hasPermission(authUser, groups.GoogleAdsence, access.Delete)

export const canAccessSurvey = async (authUser) => hasPermission(authUser, groups.Survey, access.ViewAny)
export const canCreateSurvey = async (authUser) => hasPermission(authUser, groups.Survey, access.Create)
export const canUpdateSurvey = async (authUser, survey) => hasPermission(authUser, groups.Survey, access.Update)
export const canInactiveSurvey = async (authUser, survey) => hasPermission(authUser, groups.Survey, access.Delete)
export const canActiveSurvey = async (authUser, survey) => hasPermission(authUser, groups.Survey, access.Restore)
export const canDeleteSurvey = async (authUser, survey) => hasPermission(authUser, groups.Survey, access.ForceDelete)

export const canAccessSurveyQuestion = async (authUser) => hasPermission(authUser, groups.SurveyQuestion, access.ViewAny)
export const canCreateSurveyQuestion = async (authUser) => hasPermission(authUser, groups.SurveyQuestion, access.Create)
export const canUpdateSurveyQuestion = async (authUser, surveyQuestion) => hasPermission(authUser, groups.SurveyQuestion, access.Update)
export const canDeleteSurveyQuestion = async (authUser, surveyQuestion) => hasPermission(authUser, groups.SurveyQuestion, access.Delete)

export const canAccessNewsAttributes = async (authUser) =>
    (
        await Promise.all([
            canAccessCategory(authUser),
            canAccessTag(authUser),
            canAccessTrend(authUser),
            canAccessEvent(authUser),
            canAccessLocation(authUser),
            canAccessContributor(authUser),
        ])
    ).some(Boolean)

export const canAccessActivityLog = async (authUser) => authUser?.is_super_admin
export const canDeleteActivityLog = async (authUser) => authUser?.is_super_admin

export const canAccessQueueMonitor = (authUser) => authUser?.is_super_admin

export const canAccessLogViewer = (authUser) => authUser?.is_super_admin

export const canAccessSetting = (authUser) => authUser?.is_super_admin
