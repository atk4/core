<?php

namespace atk4\core;

/**
 * App may implement this interface meaning that it is capable of properly
 * displaying user-focused messages.
 *
 * Typically those messages will be displayed through the UI as Growl or
 * Notifications
 */
interface AppUserNotificationInterface
{
    /**
     * This function will be called with a message that needs to be
     * displayed to user.
     *
     * @param string $message
     */
    public function userNotification($message, array $context = []);
}
