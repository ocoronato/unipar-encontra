@include('errors.layout', [
    'code' => 403,
    'title' => 'Acesso negado',
    'message' => 'Você não tem permissão para acessar esta página.',
])
