@include('errors.layout', [
    'code' => 500,
    'title' => 'Erro no servidor',
    'message' => 'Ocorreu um erro inesperado. Tente novamente em alguns instantes.',
])
