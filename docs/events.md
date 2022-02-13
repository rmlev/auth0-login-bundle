Events of Auth0LoginBundle
==========================

Auth0LoginBundle dispatches the following events:

* Rmlev\Auth0LoginBundle\Event\ConnectSuccessEvent
* Rmlev\Auth0LoginBundle\Event\ConnectFailureEvent

The bundle dispatches a ConnectSuccessEvent on successful authentication and a ConnectFailureEvent
on authentication failure.
You can create an event listener or event subscriber and set RedirectResponse on the event.

The example of an event subscriber:
```php

// ...
use Rmlev\Auth0LoginBundle\Event\ConnectSuccessEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ExampleConnectSuccessSubscriber implements EventSubscriberInterface
{
    // ...

    public function onConnectSuccessEvent(ConnectSuccessEvent $event)
    {
        // This redirects to URL '/redirect_on_success' in case of successful authentication
        $response = new RedirectResponse('/redirect_on_success');
        $event->setResponse($response);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConnectSuccessEvent::class => 'onConnectSuccessEvent',
        ];
    }
}
```

[Return to the index.](index.md)
