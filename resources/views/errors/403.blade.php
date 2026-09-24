{{-- Mostra o motivo definido na Policy (ex.: "Você já possui uma solicitação...") quando houver. --}}
@include('errors.layout', [
    'code' => 403,
    'title' => 'Acesso negado',
    'message' => __($exception->getMessage() ?: 'Você não tem permissão para acessar esta página.'),
])
