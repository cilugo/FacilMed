<?php

/*
|--------------------------------------------------------------------------
| Coordenadas aproximadas — busca por distância (29/09/2026)
|--------------------------------------------------------------------------
|
| Latitude e longitude APROXIMADAS do centro de cada cidade do Vale do
| Paraíba (e arredores) e de alguns bairros de São José dos Campos.
|
| Por que aqui e não num serviço de mapa (Google, Nominatim)? Porque eles
| precisam de internet e de chave na hora da banca, e o ViaCEP não traz
| coordenadas. É a mesma ideia das bases simuladas: dado fixo, conhecido,
| que roda no XAMPP sem nada externo.
|
| Como é usado (App\Support\Localizacao):
|   - todo local salvo SEM coordenada ganha a do bairro (se estiver aqui) ou
|     a do centro da cidade (Local::booted);
|   - quem escolhe a cidade na tela "Locais perto de você" é medido a partir
|     do centro dela.
|
| Cidade que não está aqui: o local fica sem coordenada e aparece no fim da
| lista, sem distância. Para incluir uma cidade, acrescente uma linha.
| Formato: [latitude, longitude] (sul e oeste são negativos).
|
*/

return [

    'cidades' => [
        'SP' => [
            'São José dos Campos' => [
                'centro'  => [-23.1794, -45.8869],
                'bairros' => [
                    'Centro'           => [-23.1794, -45.8869],
                    'Jardim Satélite'  => [-23.2253, -45.8894],
                    'Vila Ema'         => [-23.2006, -45.8999],
                    'Jardim Aquarius'  => [-23.2204, -45.9086],
                    'Vila Adyana'      => [-23.1985, -45.8930],
                    'Urbanova'         => [-23.1953, -45.9551],
                    'Santana'          => [-23.1667, -45.8870],
                    'Bosque dos Eucaliptos' => [-23.2437, -45.8963],
                ],
            ],
            'Taubaté'          => ['centro' => [-23.0264, -45.5553]],
            'Jacareí'          => ['centro' => [-23.3053, -45.9658]],
            'Caçapava'         => ['centro' => [-23.1008, -45.7069]],
            'Pindamonhangaba'  => ['centro' => [-22.9246, -45.4613]],
            'Tremembé'         => ['centro' => [-22.9575, -45.5486]],
            'Guaratinguetá'    => ['centro' => [-22.8162, -45.1925]],
            'Aparecida'        => ['centro' => [-22.8469, -45.2297]],
            'Lorena'           => ['centro' => [-22.7310, -45.1244]],
            'Campos do Jordão' => ['centro' => [-22.7394, -45.5914]],
            'Caraguatatuba'    => ['centro' => [-23.6203, -45.4131]],
            'São Sebastião'    => ['centro' => [-23.7951, -45.4143]],
            'Ubatuba'          => ['centro' => [-23.4336, -45.0838]],
            'Santa Branca'     => ['centro' => [-23.3969, -45.8838]],
            'Paraibuna'        => ['centro' => [-23.3872, -45.6622]],
            'Jambeiro'         => ['centro' => [-23.2528, -45.6938]],
            'Monteiro Lobato'  => ['centro' => [-22.9544, -45.8397]],
            'Igaratá'          => ['centro' => [-23.2045, -46.1573]],
            'Guararema'        => ['centro' => [-23.4112, -46.0369]],
            'Mogi das Cruzes'  => ['centro' => [-23.5208, -46.1854]],
            'São Paulo'        => ['centro' => [-23.5505, -46.6333]],
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | De qual cidade é o CEP (30/09/2026 — busca da home para quem está logado)
    |----------------------------------------------------------------------
    |
    | Faixas APROXIMADAS dos Correios, pelos 5 primeiros dígitos do CEP:
    | [de, até, cidade, uf]. A cidade precisa estar em 'cidades' acima - é o
    | centro dela que vira o ponto de partida da distância.
    |
    | Mesmo motivo das coordenadas: o ViaCEP precisaria de internet na banca.
    | CEP fora de todas as faixas: a tela avisa e pede para escolher a cidade
    | (App\Support\Localizacao::cidadePorCep).
    |
    */
    'ceps' => [
        [12200, 12248, 'São José dos Campos', 'SP'],
        [12000, 12119, 'Taubaté', 'SP'],
        [12120, 12129, 'Tremembé', 'SP'],
        [12250, 12250, 'Monteiro Lobato', 'SP'],
        [12260, 12260, 'Paraibuna', 'SP'],
        [12270, 12270, 'Jambeiro', 'SP'],
        [12280, 12299, 'Caçapava', 'SP'],
        [12300, 12349, 'Jacareí', 'SP'],
        [12350, 12350, 'Igaratá', 'SP'],
        [12380, 12380, 'Santa Branca', 'SP'],
        [12400, 12449, 'Pindamonhangaba', 'SP'],
        [12460, 12464, 'Campos do Jordão', 'SP'],
        [12500, 12524, 'Guaratinguetá', 'SP'],
        [12570, 12579, 'Aparecida', 'SP'],
        [12600, 12614, 'Lorena', 'SP'],
        [11600, 11629, 'São Sebastião', 'SP'],
        [11660, 11674, 'Caraguatatuba', 'SP'],
        [11680, 11699, 'Ubatuba', 'SP'],
        [8700, 8899, 'Mogi das Cruzes', 'SP'],
        [8900, 8900, 'Guararema', 'SP'],
        [1000, 5999, 'São Paulo', 'SP'],
        [8000, 8499, 'São Paulo', 'SP'],
    ],

];
