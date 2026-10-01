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
    | 01/10/2026 (plano novo do grupo): CEP → coordenada pela internet, para o
    | raio de 5/10/20 km fazer sentido (o centro do bairro é pouco para 5 km).
    |   - ViaCEP (viacep.com.br): CEP → rua, bairro, cidade, UF. Grátis, sem chave.
    |   - Nominatim (OpenStreetMap): endereço → latitude/longitude. Grátis, sem
    |     chave; pede um "User-Agent" que identifique o sistema e no máximo 1
    |     pedido por segundo (por isso o local é procurado UMA vez, ao salvar).
    | Sem internet (XAMPP offline na banca) ou com o serviço fora do ar, vale a
    | coordenada aproximada do bairro/cidade acima - nada quebra.
    | Nos testes fica DESLIGADO (phpunit.xml): teste não fala com a internet.
    */
    'servico_externo' => env('LOCALIZACAO_EXTERNA', true),
    'user_agent'      => 'FacilMed/1.0 (TCC Etec Ilza Nascimento Pintus; https://facilmed.onrender.com)',
    'timeout'         => 6,

];
