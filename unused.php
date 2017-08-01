// AuthorizationCheckerInterface $AuthorizationChecker
if (!$AuthorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
    // This test may be useless as long as UserInterface is a required argument of the controller indexAction.
    // http://symfony.com/doc/current/security.html#always-check-if-the-user-is-logged-in
    // http://symfony.com/doc/current/security.html#add-code-to-deny-access
    // with this or http://symfony.com/doc/current/security.html#securing-url-patterns-access-control
    throw new AccessDeniedException();
}
//^ http://symfony.com/doc/current/security.html#checking-to-see-if-a-user-is-logged-in-is-authenticated-fully
