{{-- Laravel only serves the admin panel and APIs; the public 404 page lives in the Next.js frontend. --}}
@include('errors.static', ['code' => $code, 'title' => $title, 'message' => $message])
