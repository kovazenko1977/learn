import json

products = [
    # WINES & VERMOUTHS
    {
        "id": 1,
        "slide_num": 5,
        "cat_en": "Wines & Vermouths",
        "cat_ru": "Вина и вермуты",
        "title_en": "Vermouth Crystal Premium Ruby",
        "title_ru": "Вермут Кристалл Премиум Рубиновый",
        "sub_en": "Fortified Strong Flavored Wine",
        "sub_ru": "Вино улучшенного качества ароматизированное «Вермут Кристалл Премиум Рубиновый»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/vermout_rubin.png",
        "desc_en": "Produced by alcoholic fermentation of sweetened apple, chokeberry (aronia), and redcurrant juices to a natural alcohol level of at least 10%, followed by addition of sugar, rectified ethyl alcohol, and natural herbal extract 'Vermouth-Kryshtal' containing wormwood, yarrow, balsam tansy, peppermint leaves, calamus rhizome, cardamom, thyme, oregano, coriander, nutmeg, and cinnamon.",
        "desc_ru": "Вино изготовлено путем спиртового брожения подсахаренных соков из яблок, черноплодной рябины, красной смородины до накопления объемной доли этилового спирта не менее 10% с последующим добавлением сахара, спирта этилового ректификованного из пищевого сырья и основы концентрированной пищевой «Вермут-Крышталь», содержащую травы полыни горькой, тысячелистника, пижмы бальзамической, листья мяты перечной, корневища аира, кардамона, травы чабреца обмолоченного и душицы, кориандр, мускатный орех, корицу.",
        "color_en": "From amber to red",
        "color_ru": "От янтарного до красного",
        "taste_en": "Harmonious, full, with notes of spicy-aromatic herbs",
        "taste_ru": "Гармоничный, полный, с тонами пряно-ароматических трав",
        "abv": "18.0%",
        "sugar": "60 g/dm³",
        "shelf": "18 months",
        "pack_en": "Glass bottle 0.5L / 0.7L",
        "pack_ru": "Стеклянная бутылка 0,5л / 0,7л",
        "raw_en": "Natural apple, chokeberry and redcurrant juices, natural herbal extract",
        "raw_ru": "Натуральные яблочный, черноплоднорябиновый и красносмородиновый соки, травяной экстракт"
    },
    {
        "id": 2,
        "slide_num": 6,
        "cat_en": "Wines & Vermouths",
        "cat_ru": "Вина и вермуты",
        "title_en": "Vermouth Crystal Premium Blueberry",
        "title_ru": "Вермут Кристалл Премиум Черничный",
        "sub_en": "Fortified Strong Flavored Wine",
        "sub_ru": "Вино плодовое крепленое крепкое улучшенного качества ароматизированное «Вермут Кристалл Премиум Черничный»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/vermout_chernichny.png",
        "desc_en": "Crafted via alcoholic fermentation of natural apple and blueberry juices to at least 10% ABV, supplemented with sugar, high-purity ethyl alcohol, and natural herbal infusion 'Vermouth-Kryshtal' featuring bitter wormwood, yarrow, peppermint, calamus, cardamom, thyme, oregano, coriander, and cinnamon.",
        "desc_ru": "Вино изготовлено путем спиртового брожения соков из яблок и черники до накопления объемной доли этилового спирта не менее 10% с последующим добавлением сахара, спирта этилового и основы концентрированной «Вермут-Крышталь», содержащую траву полыни горькой, тысячелистника, пижмы бальзамической, листья мяты перечной, корневища аира, кардамона, траву чабреца, душицы, кориандр, мускатный орех, корицу.",
        "color_en": "From golden to amber, with pinkish or red shades",
        "color_ru": "От золотистого до янтарного, с оттенками от розового до красного",
        "taste_en": "Harmonious, full with apple aroma, herbal notes and wormwood bitterness",
        "taste_ru": "Гармоничный, полный с ароматом яблок, с тонами пряно-ароматических трав и полынной горчинкой",
        "abv": "18.0%",
        "sugar": "65 g/dm³",
        "shelf": "18 months",
        "pack_en": "Glass bottle 0.5L / 0.7L",
        "pack_ru": "Стеклянная бутылка 0,5л / 0,7л",
        "raw_en": "Natural apple and blueberry juices, natural herbal extract",
        "raw_ru": "Натуральные яблочный и черничный соки, травяной экстракт"
    },
    {
        "id": 3,
        "slide_num": 7,
        "cat_en": "Wines & Vermouths",
        "cat_ru": "Вина и вермуты",
        "title_en": "Sudarushka",
        "title_ru": "Сударушка",
        "sub_en": "Fortified Strong Fruit Wine",
        "sub_ru": "Вино плодовое крепленое крепкое улучшенного качества «Сударушка»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/sudaruska.jpg",
        "desc_en": "Classic Belarusian fortified fruit wine produced from selected natural apple, chokeberry, and cranberry juices. Characterized by a rich fruity bouquet and traditional recipe.",
        "desc_ru": "Вино плодовое крепленое крепкое улучшенного качества «Сударушка». Изготовлено путем спиртового брожения подсахаренных соков из яблок, черноплодной рябины и клюквы.",
        "color_en": "From light red to dark amber",
        "color_ru": "От светло-красного до темно-янтарного",
        "taste_en": "Harmonious, apple-based with chokeberry and cranberry notes",
        "taste_ru": "Гармоничный, яблочный с тонами черноплодной рябины и клюквы",
        "abv": "18.0%",
        "sugar": "60 g/dm³",
        "shelf": "18 months",
        "pack_en": "Glass bottle 0.5L / 0.7L",
        "pack_ru": "Стеклянная бутылка 0,5л / 0,7л",
        "raw_en": "Natural apple, chokeberry and cranberry juices",
        "raw_ru": "Натуральные соки яблок, черноплодной рябины и клюквы"
    },
    {
        "id": 4,
        "slide_num": 8,
        "cat_en": "Wines & Vermouths",
        "cat_ru": "Вина и вермуты",
        "title_en": "Berry Nocturne",
        "title_ru": "Ягодный ноктюрн",
        "sub_en": "Natural Table Semi-Sweet Fruit Wine",
        "sub_ru": "Вино фруктово-ягодное натуральное столовое полусладкое «Ягодный ноктюрн»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/nocturne.png",
        "desc_en": "Light natural semi-sweet wine produced by blending processed natural apple table wine base with natural extracts of dried elderberry, chokeberry, blackcurrant, and sugar.",
        "desc_ru": "Вино изготовлено путем купажирования яблочного натурального столового обработанного виноматериала, экстракта сухих ягод бузины, аронии, черной смородины и сахаросодержащих веществ.",
        "color_en": "From light pink to red",
        "color_ru": "От светло-розового до красного",
        "taste_en": "Harmonious, light, with apple and berry bouquet",
        "taste_ru": "Гармоничный, легкий, с яблочно-ягодными тонами",
        "abv": "9.0% - 12.9%",
        "sugar": "50 g/dm³",
        "shelf": "12 months",
        "pack_en": "Glass bottle 0.7L",
        "pack_ru": "Стеклянная бутылка 0,7л",
        "raw_en": "Processed apple wine base, extracts of elderberry, chokeberry, blackcurrant",
        "raw_ru": "Яблочный виноматериал, экстракты ягод бузины, аронии, черной смородины"
    },
    {
        "id": 5,
        "slide_num": 9,
        "cat_en": "Wines & Vermouths",
        "cat_ru": "Вина и вермуты",
        "title_en": "Kirmash",
        "title_ru": "Кiрмаш",
        "sub_en": "Award-Winning Fortified Fruit Wine",
        "sub_ru": "Вино плодовое крепленое крепкое улучшенного качества «Кiрмаш»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/kirmash15071.png",
        "desc_en": "Silver Medal Winner at the International Tasting Competition 'Wines 2012' (Minsk). Produced by alcoholic fermentation of sweetened juices from fresh apples and blackcurrants followed by fortification.",
        "desc_ru": "Вино отмечено серебряной медалью на Международном конкурсе-дегустации «Вина 2012» в г. Минске. Изготовлено путем спиртового брожения подсахаренных соков из яблок и черной смородины.",
        "color_en": "From straw-yellow to dark amber, pinkish tint allowed",
        "color_ru": "От соломенного до темно-янтарного, допускается розовый оттенок",
        "taste_en": "Harmonious, apple with distinct blackcurrant tones",
        "taste_ru": "Гармоничный, яблочный с черносмородиновыми нотами",
        "abv": "18.0%",
        "sugar": "70 g/dm³",
        "shelf": "12 months",
        "pack_en": "Glass bottle 0.5L / 0.7L",
        "pack_ru": "Стеклянная бутылка 0,5л / 0,7л",
        "raw_en": "Sweetened natural apple and blackcurrant juices",
        "raw_ru": "Подсахаренные натуральные соки яблок и черной смородины"
    },
    {
        "id": 6,
        "slide_num": 10,
        "cat_en": "Wines & Vermouths",
        "cat_ru": "Вина и вермуты",
        "title_en": "Black Lady",
        "title_ru": "Черная дама",
        "sub_en": "Fortified Sweet Fruit Wine",
        "sub_ru": "Вино плодовое крепленое крепкое сладкое «Черная дама»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/chernay_dama-298x1024.jpg",
        "desc_en": "Rich, sweet fortified fruit wine combining ripe apple undertones with dense blackcurrant notes, delivering a velvety texture and deep ruby color.",
        "desc_ru": "Вино плодовое крепленое крепкое сладкое «Черная дама». Изготовлено путем спиртового брожения подсахаренных соков из яблок и черной смородины с высокой концентрацией сахара.",
        "color_en": "From light red to dark red",
        "color_ru": "От светло-красного до темно-красного",
        "taste_en": "Harmonious, full with rich apple and blackcurrant aroma",
        "taste_ru": "Гармоничный, полный с ароматом яблок и черной смородины",
        "abv": "16.0%",
        "sugar": "160 g/dm³",
        "shelf": "18 months",
        "pack_en": "Glass bottle 0.5L / 0.7L",
        "pack_ru": "Стеклянная бутылка 0,5л / 0,7л",
        "raw_en": "Natural apple and blackcurrant juices",
        "raw_ru": "Натуральные соки яблок и черной смородины"
    },
    {
        "id": 7,
        "slide_num": 11,
        "cat_en": "Wines & Vermouths",
        "cat_ru": "Вина и вермуты",
        "title_en": "Antal",
        "title_ru": "Анталь",
        "sub_en": "Fortified Strong Fruit Wine",
        "sub_ru": "Вино плодовое крепленое крепкое улучшенного качества «Анталь»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/antal91.png",
        "desc_en": "In production since November 2011, Antal quickly earned consumer acclaim. Crafted by fermentation of sweetened apple and blackcurrant juices, followed by fortification with high-purity spirit.",
        "desc_ru": "Вино выпускается с ноября 2011г. Удачно вписалось в ассортимент вин комбината, пользуется постоянным спросом. Изготовлено путем спиртового брожения соков яблок и черной смородины.",
        "color_en": "From straw-yellow to dark amber, pinkish tint allowed",
        "color_ru": "От соломенного до темно-янтарного, допускается розовый оттенок",
        "taste_en": "Harmonious, clean apple flavor with blackcurrant accents",
        "taste_ru": "Гармоничный, яблочный с черносмородиновыми оттенками",
        "abv": "18.0%",
        "sugar": "50 g/dm³",
        "shelf": "12 months",
        "pack_en": "Glass bottle 0.5L / 0.7L",
        "pack_ru": "Стеклянная бутылка 0,5л / 0,7л",
        "raw_en": "Natural apple and blackcurrant juices",
        "raw_ru": "Натуральные соки яблок и черной смородины"
    },
    {
        "id": 8,
        "slide_num": 12,
        "cat_en": "Wines & Vermouths",
        "cat_ru": "Вина и вермуты",
        "title_en": "Backouskaye",
        "title_ru": "Бацькоускае",
        "sub_en": "Natural Table Semi-Sweet Fruit Wine",
        "sub_ru": "Вино фруктово-ягодное натуральное столовое полусладкое «Бацькоускае»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/batkovskoe.png",
        "desc_en": "Traditional Belarusian semi-sweet wine produced by blending processed natural apple wine base with natural sugar-containing substances, preserving fresh apple taste.",
        "desc_ru": "Вино изготовлено путем купажирования яблочного натурального столового обработанного виноматериала и сахаросодержащих веществ.",
        "color_en": "From light amber to dark amber",
        "color_ru": "От светло-янтарного до темно-янтарного",
        "taste_en": "Harmonious, clean with pure apple notes",
        "taste_ru": "Гармоничный, чистый с яблочными тонами",
        "abv": "11.0% - 12.9%",
        "sugar": "40 g/dm³",
        "shelf": "12 months",
        "pack_en": "Glass bottle 0.7L",
        "pack_ru": "Стеклянная бутылка 0,7л",
        "raw_en": "Natural apple wine material",
        "raw_ru": "Натуральный обработанный яблочный виноматериал"
    },
    {
        "id": 9,
        "slide_num": 13,
        "cat_en": "Wines & Vermouths",
        "cat_ru": "Вина и вермуты",
        "title_en": "Berry Quartet",
        "title_ru": "Ягодный квартет",
        "sub_en": "Natural Table Semi-Sweet Fruit Wine",
        "sub_ru": "Вино фруктово-ягодное натуральное столовое полусладкое «Ягодный квартет»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/quartet.png",
        "desc_en": "A rich 4-berry blend crafted from processed apple wine base infused with dried extracts of elderberry, cranberry, cowberry (lingonberry), and blackberry.",
        "desc_ru": "Вино изготовлено путем купажирования яблочного натурального столового обработанного виноматериала, экстракта сухих ягод бузины, клюквы, брусники, ежевики и сахаросодержащих веществ.",
        "color_en": "From light amber to dark amber with pinkish tint",
        "color_ru": "От светло-янтарного до темно-янтарного с розовым оттенком",
        "taste_en": "Harmonious, complex apple-berry bouquet",
        "taste_ru": "Гармоничный, с яблочно-ягодными тонами",
        "abv": "11.0% - 12.9%",
        "sugar": "45 g/dm³",
        "shelf": "12 months",
        "pack_en": "Glass bottle 0.7L",
        "pack_ru": "Стеклянная бутылка 0,7л",
        "raw_en": "Apple wine base, extracts of elderberry, cranberry, cowberry, blackberry",
        "raw_ru": "Яблочный виноматериал, экстракты бузины, клюквы, брусники, ежевики"
    },

    # FRUIT CIDERS
    {
        "id": 10,
        "slide_num": 14,
        "cat_en": "Fruit Ciders",
        "cat_ru": "Сидры",
        "title_en": "Apple Boom (Dry)",
        "title_ru": "Эпл Бум (Сухой)",
        "sub_en": "Sparkling Dry Fruit-Berry Cider",
        "sub_ru": "Сидр фруктово-ягодный газированный сухой «Эпл Бум» («Apple Boom»)",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/apple-boom-04-suh-481x1024.png",
        "desc_en": "Naturally fermented sparkling cider crafted from 100% natural apple juice. Delivers crisp acidity, fine effervescence, and clean dry fruit profile.",
        "desc_ru": "Сидр фруктово-ягодный газированный сухой «Эпл Бум». Изготовлен путем сбраживания натурального яблочного сока. Обладает свежим сухим вкусом и естественной газацией.",
        "color_en": "From light straw to amber",
        "color_ru": "От светло-соломенного до янтарного",
        "taste_en": "Harmonious, fresh apple flavor",
        "taste_ru": "Гармоничный, яблочный",
        "abv": "5.7%",
        "sugar": "≤ 3.0 g/dm³",
        "shelf": "16 months",
        "pack_en": "Glass bottle 0.4L",
        "pack_ru": "Стеклянная бутылка 0,4л",
        "raw_en": "100% natural fermented apple juice",
        "raw_ru": "100% натуральный сброженный яблочный сок"
    },
    {
        "id": 11,
        "slide_num": 15,
        "cat_en": "Fruit Ciders",
        "cat_ru": "Сидры",
        "title_en": "Apple Boom (Semi-Sweet)",
        "title_ru": "Эпл Бум (Полусладкий)",
        "sub_en": "Sparkling Semi-Sweet Fruit-Berry Cider",
        "sub_ru": "Сидр фруктово-ягодный газированный полусладкий «Эпл Бум» («Apple Boom»)",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/apple-boom-04-p-sl-481x1024.png",
        "desc_en": "Naturally fermented sparkling semi-sweet cider with a rich golden hue and vibrant crisp apple sweetness, perfect for modern refreshing aperitifs.",
        "desc_ru": "Сидр фруктово-ягодный газированный полусладкий «Эпл Бум». Натуральный сброженный напиток с мягким полусладким яблочным вкусом и приятным ароматом.",
        "color_en": "From light straw to amber",
        "color_ru": "От светло-соломенного до янтарного",
        "taste_en": "Harmonious, sweet juicy apple bouquet",
        "taste_ru": "Гармоничный, сочный яблочный",
        "abv": "5.7%",
        "sugar": "50.0 g/dm³",
        "shelf": "16 months",
        "pack_en": "Glass bottle 0.4L",
        "pack_ru": "Стеклянная бутылка 0,4л",
        "raw_en": "100% natural fermented apple juice",
        "raw_ru": "100% натуральный сброженный яблочный сок"
    },

    # LOW ALCOHOL DRINKS CHINAZES
    {
        "id": 12,
        "slide_num": 16,
        "cat_en": "Low-Alcohol Beverages",
        "cat_ru": "Слабоалкогольные напитки",
        "title_en": "Chinazes Cherry & Cinnamon",
        "title_ru": "Чиназес со вкусом Вишни",
        "sub_en": "Natural Carbonated Low-Alcohol Beverage",
        "sub_ru": "Напиток слабоалкогольный натуральный газированный «ЧИНАЗЕС СО ВКУСОМ ВИШНИ»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/chinazesvishnja04-481x1024.png",
        "desc_en": "Trendy natural carbonated low-alcohol drink blending sweet ripe cherry with subtle honey notes and warm spice hints of cinnamon.",
        "desc_ru": "Напиток слабоалкогольный натуральный газированный со вкусом вишни. Гармоничное сочетание спелой вишни, медового оттенка и пряных ноток корицы.",
        "color_en": "From pink to red",
        "color_ru": "От розового до красного",
        "taste_en": "Harmonious, cherry with honey notes and cinnamon hints",
        "taste_ru": "Гармоничный, вишнёвый с медовым оттенком и нотами корицы",
        "abv": "5.0%",
        "sugar": "70 g/dm³",
        "shelf": "12 months",
        "pack_en": "Glass bottle 0.4L",
        "pack_ru": "Стеклянная бутылка 0,4л",
        "raw_en": "Natural juice base, natural cherry flavor, honey and cinnamon extract",
        "raw_ru": "Натуральная соковая основа, ароматизатор вишни, экстракт меда и корицы"
    },
    {
        "id": 13,
        "slide_num": 17,
        "cat_en": "Low-Alcohol Beverages",
        "cat_ru": "Слабоалкогольные напитки",
        "title_en": "Chinazes Pear & Honey",
        "title_ru": "Чиназес со вкусом Груши",
        "sub_en": "Natural Carbonated Low-Alcohol Beverage",
        "sub_ru": "Напиток слабоалкогольный натуральный газированный «ЧИНАЗЕС СО ВКУСОМ ГРУШИ»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/chinazesgrusha04-481x1024.png",
        "desc_en": "Refreshing sparkling low-alcohol beverage featuring juicy Duchess pear aroma balanced with delicate golden honey sweetness.",
        "desc_ru": "Напиток слабоалкогольный натуральный газированный со вкусом груши. Освежающий вкус сочной груши дюшес со сбалансированной медовой сладостью.",
        "color_en": "From straw to amber",
        "color_ru": "От соломенного до янтарного",
        "taste_en": "Harmonious, pear with honey undertones",
        "taste_ru": "Гармоничный, грушевый с медовым оттенком",
        "abv": "5.0%",
        "sugar": "70 g/dm³",
        "shelf": "12 months",
        "pack_en": "Glass bottle 0.4L",
        "pack_ru": "Стеклянная бутылка 0,4л",
        "raw_en": "Natural juice base, pear flavor, natural honey extract",
        "raw_ru": "Натуральная соковая основа, грушевый ароматизатор, натуральный экстракт меда"
    },
    {
        "id": 14,
        "slide_num": 18,
        "cat_en": "Low-Alcohol Beverages",
        "cat_ru": "Слабоалкогольные напитки",
        "title_en": "Chinazes Lemon & Honey",
        "title_ru": "Чиназес со вкусом Лимона",
        "sub_en": "Natural Carbonated Low-Alcohol Beverage",
        "sub_ru": "Напиток слабоалкогольный натуральный газированный «ЧИНАЗЕС СО ВКУСОМ ЛИМОНА»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/02/chinazeslimon04-481x1024.png",
        "desc_en": "Invigorating carbonated drink combining zesty Sicilian lemon citrus sharpness with smooth, soothing honey sweetness.",
        "desc_ru": "Напиток слабоалкогольный натуральный газированный со вкусом лимона. Бодрящий цитрусовый вкус лимона с мягкой медовой нотой.",
        "color_en": "From light yellow to lemon yellow",
        "color_ru": "От светло-жёлтого до лимонно-жёлтого",
        "taste_en": "Harmonious, citrus lemon with honey hints",
        "taste_ru": "Гармоничный, лимонный с медовым оттенком",
        "abv": "5.0%",
        "sugar": "70 g/dm³",
        "shelf": "12 months",
        "pack_en": "Glass bottle 0.4L",
        "pack_ru": "Стеклянная бутылка 0,4л",
        "raw_en": "Natural juice base, citrus lemon extract, natural honey extract",
        "raw_ru": "Натуральная соковая основа, экстракт лимона, натуральный экстракт меда"
    },

    # YOUTH & LIFESTYLE LINE
    {
        "id": 15,
        "slide_num": 19,
        "cat_en": "Youth & Lifestyle Line",
        "cat_ru": "Молодежная линейка",
        "title_en": "Zero Bianco (Non-Alcoholic)",
        "title_ru": "Zero Bianco (Безалкогольный)",
        "sub_en": "Non-Alcoholic Lifestyle Beverage",
        "sub_ru": "Безалкогольный напиток «Zero Bianco»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/06/Copilot_20260530_202435-683x1024.png",
        "desc_en": "Zero alcohol — 100% freedom. Created for drive, sports, active living, and clean taste. Light and crisp like the morning after victory.",
        "desc_ru": "Ноль алкоголя — сто процентов свободы. Для тех, кто выбирает драйв, спорт, движение и чистый вкус жизни. Лёгкий, свежий, как утро после победы.",
        "color_en": "Crystal clear, light straw",
        "color_ru": "Кристально-прозрачный, светло-соломенный",
        "taste_en": "Crisp, refreshing, light grape-citrus notes",
        "taste_ru": "Свежий, бодрящий, легкие виноградно-цитрусовые тона",
        "abv": "0.0%",
        "sugar": "Not specified in official source",
        "shelf": "12 months",
        "pack_en": "Glass bottle 0.75L",
        "pack_ru": "Стеклянная бутылка 0,75л",
        "raw_en": "Dealcoholized juice base, botanical extracts",
        "raw_ru": "Деалкоголизированная соковая основа, растительные экстракты"
    },
    {
        "id": 16,
        "slide_num": 20,
        "cat_en": "Youth & Lifestyle Line",
        "cat_ru": "Молодежная линейка",
        "title_en": "Rosso Allegro",
        "title_ru": "Rosso Allegro",
        "sub_en": "Vibrant Red Lifestyle Beverage",
        "sub_ru": "Виноградный напиток «Rosso Allegro»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/06/Copilot_20260530_202236-683x1024.png",
        "desc_en": "Red means passion. Made for those who aren't afraid to shine bright. Every sip bursts with emotion like a dance under night lights.",
        "desc_ru": "Красное — значит страсть. Это напиток для тех, кто не боится быть ярким. Каждый глоток — как вспышка эмоций, как танец под любимый трек.",
        "color_en": "Deep vibrant ruby red",
        "color_ru": "Насыщенный рубиново-красный",
        "taste_en": "Passionate, rich red berry bouquet",
        "taste_ru": "Страстный, сочный вкус красных ягод",
        "abv": "Not specified in official source",
        "sugar": "Not specified in official source",
        "shelf": "12 months",
        "pack_en": "Glass bottle 0.75L",
        "pack_ru": "Стеклянная бутылка 0,75л",
        "raw_en": "Natural grape and berry base",
        "raw_ru": "Натуральная виноградно-ягодная основа"
    },
    {
        "id": 17,
        "slide_num": 21,
        "cat_en": "Youth & Lifestyle Line",
        "cat_ru": "Молодежная линейка",
        "title_en": "Belloro",
        "title_ru": "Belloro",
        "sub_en": "Classic White Lifestyle Beverage",
        "sub_ru": "Напиток винный «Belloro»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/06/Copilot_20260530_202908-683x1024.png",
        "desc_en": "White classic with character. Pure, fresh, confident — for people who know what they want. Perfect for gatherings starting with a smile.",
        "desc_ru": "Белая классика с характером. Чистый, свежий, уверенный — как человек, который знает, чего хочет. Идеален для встреч, где всё начинается с улыбки.",
        "color_en": "Golden straw with green highlights",
        "color_ru": "Золотисто-соломенный с зеленоватым оттенком",
        "taste_en": "Clean, confident, well-balanced fruity taste",
        "taste_ru": "Чистый, уверенный, сбалансированный фруктовый вкус",
        "abv": "Not specified in official source",
        "sugar": "Not specified in official source",
        "shelf": "12 months",
        "pack_en": "Glass bottle 0.75L",
        "pack_ru": "Стеклянная бутылка 0,75л",
        "raw_en": "Natural white fruit wine material",
        "raw_ru": "Натуральный белый фруктовый виноматериал"
    },
    {
        "id": 18,
        "slide_num": 22,
        "cat_en": "Youth & Lifestyle Line",
        "cat_ru": "Молодежная линейка",
        "title_en": "Lumino",
        "title_ru": "Lumino",
        "sub_en": "Light Rosé Lifestyle Beverage",
        "sub_ru": "Напиток розовый «Lumino»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/06/Copilot_20260530_202117-683x1024.png",
        "desc_en": "Pink radiance, lightness, and flirtation. Designed for those living in the rhythm of sunset parties. Tastes like a cool breeze with friends.",
        "desc_ru": "Розовое сияние, лёгкость и флирт. Создан для тех, кто живёт в ритме вечеринок и закатов. Вкус — как первый глоток после смеха с друзьями.",
        "color_en": "Delicate glowing rose pink",
        "color_ru": "Нежный сияющий розовый",
        "taste_en": "Light, subtle floral-berry notes",
        "taste_ru": "Легкий, утонченный с цветочно-ягодными оттенками",
        "abv": "Not specified in official source",
        "sugar": "Not specified in official source",
        "shelf": "12 months",
        "pack_en": "Glass bottle 0.75L",
        "pack_ru": "Стеклянная бутылка 0,75л",
        "raw_en": "Natural light fruit blend",
        "raw_ru": "Натуральный светлый фруктовый купаж"
    },

    # POTATO & MODIFIED STARCHES
    {
        "id": 19,
        "slide_num": 23,
        "cat_en": "Potato & Modified Starches",
        "cat_ru": "Картофельный и модифицированный крахмал",
        "title_en": "Potato Starch (GOST 7699-78)",
        "title_ru": "Крахмал картофельный (ГОСТ 7699-78)",
        "sub_en": "Grades: Extra, Superior, 1st Grade, 2nd Grade",
        "sub_ru": "Сорта: «Экстра», «Высший», «Первый», «Второй»",
        "img": "https://alco.by/instal/wp-content/uploads/2026/01/ChatGPT-Image-25-янв.-2026-г.-15_15_05-1024x683.png",
        "desc_en": "High-purity natural potato starch produced in 4 official grades under GOST 7699-78. Widely used in confectionery, meat/dairy processing, bakery, pharmaceutical tablet filling, dextrin production, textile, and paper industries.",
        "desc_ru": "Высокоочищенный натуральный картофельный крахмал четырех сортов по ГОСТ 7699-78. Применяется в кондитерской, мясо-молочной, хлебопекарной, фармацевтической, текстильной и бумажной отраслях.",
        "color_en": "White to off-white powder",
        "color_ru": "Белый порошок с кристаллическим блеском",
        "taste_en": "Neutral odor and taste characteristic of potato starch",
        "taste_ru": "Нейтральный запах и вкус, свойственный крахмалу",
        "abv": "0.0% (Food Raw Material)",
        "sugar": "0.0 g/dm³",
        "shelf": "24 months",
        "pack_en": "PP bags with PE liner 40kg / Paper packets 500g",
        "pack_ru": "Мешки ПП с ПЭ вкладышем 40 кг / Бумажные пакеты 500г",
        "raw_en": "100% selected agricultural potatoes",
        "raw_ru": "100% отборный продовольственный картофель"
    },
    {
        "id": 20,
        "slide_num": 24,
        "cat_en": "Potato & Modified Starches",
        "cat_ru": "Картофельный и модифицированный крахмал",
        "title_en": "Oxidized Starch 'Oxyamyl OPV' (E 1404)",
        "title_ru": "Крахмал окисленный «Оксиамил ОПВ» (E 1404)",
        "sub_en": "Food Additive / Modified Starch (TU BY 690277551.003-2006)",
        "sub_ru": "Пищевая добавка / Модифицированный крахмал ТУ BY 690277551.003-2006",
        "img": "https://alco.by/instal/wp-content/uploads/2026/01/mFruSFX2M8AU.png",
        "desc_en": "Modified oxidized potato starch with high gelling capabilities. Functions as emulsifier, thickener, binder, and carrier in ice cream, jelly products, Turkish delight, and industrial technical applications.",
        "desc_ru": "Крахмал окисленный обладает желирующими способностями и применяется в качестве эмульгатора, загустителя, связующего при производстве мороженого, желейных изделий, лукума и технических целей.",
        "color_en": "White fine uniform powder",
        "color_ru": "Белый однородный порошок",
        "taste_en": "Neutral, free from foreign odor",
        "taste_ru": "Нейтральный, без посторонних запахов",
        "abv": "0.0% (Food Additive)",
        "sugar": "0.0 g/dm³",
        "shelf": "24 months",
        "pack_en": "Polypropylene bags with PE liner 40 kg",
        "pack_ru": "Мешки полипропиленовые с ПЭ вкладышем 40 кг",
        "raw_en": "Chemically modified potato starch E 1404",
        "raw_ru": "Модифицированный картофельный крахмал Е 1404"
    }
]

# Generate product cards HTML
def render_product_slide(p):
    return f'''
    <!-- Slide {p["slide_num"]}: {p["title_en"]} -->
    <section class="slide" id="slide-{p["slide_num"]}">
        <div class="product-card-container">
            <div class="product-card-header">
                <div class="product-badge">
                    <span class="product-cat-tag" data-en="{p["cat_en"]}" data-ru="{p["cat_ru"]}">{p["cat_en"]}</span>
                    <span class="product-num-tag">NO. {p["id"]:02d} / 20</span>
                </div>
                <img src="https://alco.by/instal/wp-content/uploads/2026/01/200-3.png" alt="Veselovo Logo" class="product-card-logo">
            </div>

            <div class="product-card-body">
                <div class="product-img-wrapper">
                    <img src="{p["img"]}" alt="{p["title_en"]}" class="product-main-img">
                </div>

                <div class="product-details-wrapper">
                    <h2 class="product-title" data-en="{p["title_en"]}" data-ru="{p["title_ru"]}">{p["title_en"]}</h2>
                    <div class="product-subtitle" data-en="{p["sub_en"]}" data-ru="{p["sub_ru"]}">{p["sub_en"]}</div>
                    <p class="product-desc" data-en="{p["desc_en"]}" data-ru="{p["desc_ru"]}">{p["desc_en"]}</p>

                    <div class="specs-grid">
                        <div class="spec-box">
                            <span class="spec-label" data-en="Alcohol By Volume" data-ru="Крепость (спирт)">Alcohol By Volume</span>
                            <span class="spec-value">{p["abv"]}</span>
                        </div>
                        <div class="spec-box">
                            <span class="spec-label" data-en="Sugar Content" data-ru="Содержание сахара">Sugar Content</span>
                            <span class="spec-value">{p["sugar"]}</span>
                        </div>
                        <div class="spec-box">
                            <span class="spec-label" data-en="Shelf Life" data-ru="Срок годности">Shelf Life</span>
                            <span class="spec-value">{p["shelf"]}</span>
                        </div>
                        <div class="spec-box">
                            <span class="spec-label" data-en="Packaging" data-ru="Упаковка и фасовка">Packaging</span>
                            <span class="spec-value" data-en="{p["pack_en"]}" data-ru="{p["pack_ru"]}">{p["pack_en"]}</span>
                        </div>
                        <div class="spec-box full-width">
                            <span class="spec-label" data-en="Color & Visuals" data-ru="Цвет">Color & Visuals</span>
                            <span class="spec-value-small" data-en="{p["color_en"]}" data-ru="{p["color_ru"]}">{p["color_en"]}</span>
                        </div>
                        <div class="spec-box full-width">
                            <span class="spec-label" data-en="Taste & Aroma Profile" data-ru="Вкус и аромат">Taste & Aroma Profile</span>
                            <span class="spec-value-small" data-en="{p["taste_en"]}" data-ru="{p["taste_ru"]}">{p["taste_en"]}</span>
                        </div>
                        <div class="spec-box full-width">
                            <span class="spec-label" data-en="Raw Ingredients" data-ru="Состав и сырье">Raw Ingredients</span>
                            <span class="spec-value-small" data-en="{p["raw_en"]}" data-ru="{p["raw_ru"]}">{p["raw_en"]}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    '''

print("Product generator function ready.")
