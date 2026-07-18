<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Шаблон страницы настроек для плагина Premium Forest Parallax (Полностью на русском языке)
 *
 * @var array $settings Текущая сохраненная конфигурация.
 */
?>
<div class="wrap premium-forest-wrap">
	<div class="premium-forest-header">
		<div>
			<h1>Premium Forest Parallax — Настройки Эффекта Леса</h1>
			<p style="margin: 5px 0 0; opacity: 0.9; font-size: 14px;">
				<?php esc_html_e( 'Добавьте премиальный, живой интерактивный эффект колышущихся лесных ветвей и падающих листьев по краям вашего сайта. Работает на WebGL2 (Three.js) с мягким откатом в Canvas2D на старых устройствах.', 'premium-forest-parallax' ); ?>
			</p>
		</div>
		<div class="author-tag">
			<?php echo esc_html__( 'Автор:', 'premium-forest-parallax' ) . ' <strong>Коваженко С.Б.</strong><br>'; ?>
			<?php echo esc_html__( 'Разработчик:', 'premium-forest-parallax' ) . ' <a href="https://wes.by" target="_blank">wes.by</a>'; ?>
		</div>
	</div>

	<form method="post" action="options.php">
		<?php settings_fields( 'premium_forest_parallax_settings_group' ); ?>

		<div class="premium-forest-body">
			<!-- Боковая панель вкладок -->
			<div class="premium-forest-tabs">
				<a href="#" class="premium-forest-tab-link active" data-tab="general"><?php esc_html_e( 'Основные', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="leaves"><?php esc_html_e( 'Листья', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="branches" style="background: #f1fbf0; color: #1e3c1a;"><?php esc_html_e( '🌲 Ветви и Лес', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="overlays" style="background: #eef9ff; color: #104c6e;"><?php esc_html_e( '🖼️ Рамки-Наложения', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="offsets" style="background: #f7efff; color: #51106e; font-weight: 600;"><?php esc_html_e( '📐 Сдвиг и Превью', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="wind"><?php esc_html_e( 'Ветер', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="particles"><?php esc_html_e( 'Частицы', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="lighting"><?php esc_html_e( 'Освещение', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="fog"><?php esc_html_e( 'Туман', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="parallax"><?php esc_html_e( 'Параллакс', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="webgl"><?php esc_html_e( 'WebGL2', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="performance"><?php esc_html_e( 'Оптимизация', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="mobile" style="background: #fff5f5; color: #a42c2c;"><?php esc_html_e( '📱 Мобильные', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="advanced"><?php esc_html_e( 'Код и CSS', 'premium-forest-parallax' ); ?></a>
				<a href="#" class="premium-forest-tab-link" data-tab="help" style="background: #fffcf0; color: #b77a00; font-weight: bold; border-left: 3px solid #e29f00;"><?php esc_html_e( '📚 Справка', 'premium-forest-parallax' ); ?></a>
			</div>

			<!-- Содержимое вкладок -->
			<div class="premium-forest-content">

				<!-- 1. Основные -->
				<div id="tab-general" class="premium-forest-tab-content active">
					<h2><?php esc_html_e( 'Основные настройки плагина', 'premium-forest-parallax' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Включить эффект леса', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[enabled]" value="1" <?php checked( $settings['enabled'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Активирует премиальный лесной параллакс глобально на всех страницах сайта.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Автозагрузка ресурсов', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[autoload]" value="1" <?php checked( $settings['autoload'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Автоматически подключает WebGL, CSS и JS скрипты на страницах фронтенда.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Только для страниц с ID', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="text" name="premium_forest_parallax_settings[target_pages]" value="<?php echo esc_attr( implode( ',', (array) $settings['target_pages'] ) ); ?>" />
								<span class="premium-forest-desc"><?php esc_html_e( 'Укажите ID страниц или постов через запятую, чтобы показывать эффект только на них. Оставьте пустым для всего сайта.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Исключить страницы с ID', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="text" name="premium_forest_parallax_settings[exclude_pages]" value="<?php echo esc_attr( implode( ',', (array) $settings['exclude_pages'] ) ); ?>" />
								<span class="premium-forest-desc"><?php esc_html_e( 'Укажите ID страниц, на которых эффект должен быть гарантированно отключен (например, личный кабинет, оформление заказа).', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Исчезновение при клике (Desktop)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[desktop_fade_on_click]" value="1" <?php checked( $settings['desktop_fade_on_click'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Включает плавное исчезновение леса при любом клике мыши на ПК версии, чтобы не отвлекать пользователя от контента.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Длительность исчезновения на ПК (мс)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[desktop_fade_out_duration]" value="<?php echo esc_attr( $settings['desktop_fade_out_duration'] ); ?>" min="100" max="5000" />
								<span class="premium-forest-desc"><?php esc_html_e( 'Сколько миллисекунд длится плавное растворение на ПК (по умолчанию 800 мс).', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Возврат ПК-эффекта при бездействии (мс)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[desktop_reappear_delay]" value="<?php echo esc_attr( $settings['desktop_reappear_delay'] ); ?>" min="1000" max="30000" />
								<span class="premium-forest-desc"><?php esc_html_e( 'Время отсутствия кликов/бездействия в миллисекундах перед возвратом лесного эффекта на ПК (по умолчанию 4000 мс / 4 сек).', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
					</table>
				</div>

				<!-- 2. Листья -->
				<div id="tab-leaves" class="premium-forest-tab-content">
					<h2><?php esc_html_e( 'Настройка падающих листьев', 'premium-forest-parallax' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Включить отображение листьев', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[leaf_enabled]" value="1" <?php checked( $settings['leaf_enabled'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Снимите галочку, чтобы полностью отключить/скрыть все падающие и колышущиеся листья.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Режим движения листьев', 'premium-forest-parallax' ); ?></th>
							<td>
								<select name="premium_forest_parallax_settings[leaf_mode]">
									<option value="falling" <?php selected( $settings['leaf_mode'], 'falling' ); ?>><?php esc_html_e( 'Падающие сверху листья (сверху вниз)', 'premium-forest-parallax' ); ?></option>
									<option value="swaying" <?php selected( $settings['leaf_mode'], 'swaying' ); ?>><?php esc_html_e( 'Статичные колышущиеся ветки (по кругу экрана)', 'premium-forest-parallax' ); ?></option>
								</select>
								<span class="premium-forest-desc"><?php esc_html_e( 'Выберите режим "Статичные колышущиеся ветки", чтобы листья были равномерно распределены по краям и кругу экрана, колыхаясь в такт ветру, без постоянного падения вниз.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Количество листьев', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[leaf_count]" value="<?php echo esc_attr( $settings['leaf_count'] ); ?>" min="5" max="150" />
								<span class="premium-forest-desc"><?php esc_html_e( 'Количество одновременно симулируемых листьев на экране.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Минимальный размер (px)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[leaf_size_min]" value="<?php echo esc_attr( $settings['leaf_size_min'] ); ?>" min="5" max="100" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Максимальный размер (px)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[leaf_size_max]" value="<?php echo esc_attr( $settings['leaf_size_max'] ); ?>" min="10" max="250" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Прозрачность листьев (%)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[leaf_opacity]" value="<?php echo esc_attr( $settings['leaf_opacity'] ); ?>" min="10" max="100" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Случайные оттенки', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[leaf_random_color]" value="1" <?php checked( $settings['leaf_random_color'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Включает плавную органическую тонировку листьев для создания эффекта осеннего или весеннего леса.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Разновидность леса (Набор SVG)', 'premium-forest-parallax' ); ?></th>
							<td>
								<select name="premium_forest_parallax_settings[leaf_svg_set]">
									<option value="birch" <?php selected( $settings['leaf_svg_set'], 'birch' ); ?>><?php esc_html_e( 'Только берёза', 'premium-forest-parallax' ); ?></option>
									<option value="oak" <?php selected( $settings['leaf_svg_set'], 'oak' ); ?>><?php esc_html_e( 'Только дуб', 'premium-forest-parallax' ); ?></option>
									<option value="linden" <?php selected( $settings['leaf_svg_set'], 'linden' ); ?>><?php esc_html_e( 'Только липа', 'premium-forest-parallax' ); ?></option>
									<option value="maple" <?php selected( $settings['leaf_svg_set'], 'maple' ); ?>><?php esc_html_e( 'Только клён', 'premium-forest-parallax' ); ?></option>
									<option value="aspen" <?php selected( $settings['leaf_svg_set'], 'aspen' ); ?>><?php esc_html_e( 'Только осина', 'premium-forest-parallax' ); ?></option>
									<option value="mixed" <?php selected( $settings['leaf_svg_set'], 'mixed' ); ?>><?php esc_html_e( 'Смешанный лес (Все типы листьев)', 'premium-forest-parallax' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Своё изображение листа', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="text" id="premium_custom_leaf_image" name="premium_forest_parallax_settings[custom_leaf_image]" value="<?php echo esc_url( $settings['custom_leaf_image'] ); ?>" style="width: 70%; max-width: 300px;" />
								<button class="button premium-media-upload" data-input="premium_custom_leaf_image"><?php esc_html_e( 'Выбрать', 'premium-forest-parallax' ); ?></button>
								<span class="premium-forest-desc"><?php esc_html_e( 'Загрузите прозрачный PNG/SVG файл собственного листа, чтобы использовать его вместо стандартных.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
					</table>
				</div>

				<!-- 2.5 Ветви и Лес -->
				<div id="tab-branches" class="premium-forest-tab-content">
					<h2><?php esc_html_e( 'Настройка обрамления ветвей леса по краям экрана', 'premium-forest-parallax' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Включить ветви по бокам', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[edge_branches_enabled]" value="1" <?php checked( $settings['edge_branches_enabled'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Отображает густые качающиеся лесные ветки по левому и правому краям экрана.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Ширина лесного края (px)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[edge_branches_width]" value="<?php echo esc_attr( $settings['edge_branches_width'] ); ?>" min="50" max="500" />
								<span class="premium-forest-desc"><?php esc_html_e( 'На сколько пикселей ветки и листья будут заходить на экран с боков.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Густота листвы (плотность)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[edge_branches_density]" value="<?php echo esc_attr( $settings['edge_branches_density'] ); ?>" min="2" max="25" />
								<span class="premium-forest-desc"><?php esc_html_e( 'Количество подветвей и наслоений зелени.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Основной цвет листьев', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="text" name="premium_forest_parallax_settings[edge_branches_color]" value="<?php echo esc_attr( $settings['edge_branches_color'] ); ?>" />
								<span class="premium-forest-desc"><?php esc_html_e( 'HEX-цвет листьев на ветках (например, #3d6a24).', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Цвет теней листьев', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="text" name="premium_forest_parallax_settings[edge_branches_color2]" value="<?php echo esc_attr( $settings['edge_branches_color2'] ); ?>" />
								<span class="premium-forest-desc"><?php esc_html_e( 'HEX-цвет затенённых глубоких слоёв ветвей (например, #2f541c).', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Скорость покачивания ветвей', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[edge_branches_sway_speed]" value="<?php echo esc_attr( $settings['edge_branches_sway_speed'] ); ?>" min="1" max="50" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Амплитуда колыхания (px)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[edge_branches_sway_amplitude]" value="<?php echo esc_attr( $settings['edge_branches_sway_amplitude'] ); ?>" min="1" max="100" />
								<span class="premium-forest-desc"><?php esc_html_e( 'Максимальное смещение ветки от ветра в пикселях.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Своё изображение ветки', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="text" id="premium_custom_branch_image" name="premium_forest_parallax_settings[custom_branch_image]" value="<?php echo esc_url( $settings['custom_branch_image'] ); ?>" style="width: 70%; max-width: 300px;" />
								<button class="button premium-media-upload" data-input="premium_custom_branch_image"><?php esc_html_e( 'Выбрать', 'premium-forest-parallax' ); ?></button>
								<span class="premium-forest-desc"><?php esc_html_e( 'Загрузите прозрачный PNG/SVG файл цельной ветки или куста. При загрузке этот спрайт заменит процедурные ветки, сделав лесное обрамление невероятно фотореалистичным.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
					</table>
				</div>

				<!-- 2.7 Рамки-Наложения -->
				<div id="tab-overlays" class="premium-forest-tab-content">
					<h2><?php esc_html_e( 'Режим наложения фоновых рамок (0 отступы от краёв)', 'premium-forest-parallax' ); ?></h2>
					<p style="font-style: italic; color: #646970; margin-bottom: 20px;">
						<?php esc_html_e( 'Этот режим позволяет загрузить качественные готовые PNG/SVG изображения ветвей деревьев или лесных панорам и плотно закрепить их по самым краям экрана с отступами 0px. Можно выключить анимацию для 100% экономии батареи (0% нагрузки на CPU/GPU), создав роскошную статичную атмосферу лесного сайта.', 'premium-forest-parallax' ); ?>
					</p>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Включить режим рамок-наложений', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[overlay_mode_enabled]" value="1" <?php checked( $settings['overlay_mode_enabled'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Активирует плотные лесные рамки по краям экрана.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Изображение СЛЕВА', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="text" id="premium_overlay_image_left" name="premium_forest_parallax_settings[overlay_image_left]" value="<?php echo esc_url( $settings['overlay_image_left'] ); ?>" style="width: 70%; max-width: 300px;" />
								<button class="button premium-media-upload" data-input="premium_overlay_image_left"><?php esc_html_e( 'Выбрать', 'premium-forest-parallax' ); ?></button>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Изображение СПРАВА', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="text" id="premium_overlay_image_right" name="premium_forest_parallax_settings[overlay_image_right]" value="<?php echo esc_url( $settings['overlay_image_right'] ); ?>" style="width: 70%; max-width: 300px;" />
								<button class="button premium-media-upload" data-input="premium_overlay_image_right"><?php esc_html_e( 'Выбрать', 'premium-forest-parallax' ); ?></button>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Изображение СВЕРХУ', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="text" id="premium_overlay_image_top" name="premium_forest_parallax_settings[overlay_image_top]" value="<?php echo esc_url( $settings['overlay_image_top'] ); ?>" style="width: 70%; max-width: 300px;" />
								<button class="button premium-media-upload" data-input="premium_overlay_image_top"><?php esc_html_e( 'Выбрать', 'premium-forest-parallax' ); ?></button>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Изображение СНИЗУ', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="text" id="premium_overlay_image_bottom" name="premium_forest_parallax_settings[overlay_image_bottom]" value="<?php echo esc_url( $settings['overlay_image_bottom'] ); ?>" style="width: 70%; max-width: 300px;" />
								<button class="button premium-media-upload" data-input="premium_overlay_image_bottom"><?php esc_html_e( 'Выбрать', 'premium-forest-parallax' ); ?></button>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Прозрачность рамок (%)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[overlay_opacity]" value="<?php echo esc_attr( $settings['overlay_opacity'] ); ?>" min="10" max="100" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Ширина рамок на экране (px)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[overlay_width]" value="<?php echo esc_attr( $settings['overlay_width'] ); ?>" min="50" max="1000" />
								<span class="premium-forest-desc"><?php esc_html_e( 'Определяет ширину боковых рамок и высоту верхней/нижней рамки на дисплее.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Сила параллакса при движении мыши', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[overlay_parallax_strength]" value="<?php echo esc_attr( $settings['overlay_parallax_strength'] ); ?>" min="0" max="50" />
								<span class="premium-forest-desc"><?php esc_html_e( 'Укажите 0, чтобы полностью отключить любую анимацию/движение и получить статичные рамки без нагрузки на систему.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Время появления при загрузке (мс)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[overlay_fade_in_duration]" value="<?php echo esc_attr( $settings['overlay_fade_in_duration'] ); ?>" min="100" max="10000" />
								<span class="premium-forest-desc"><?php esc_html_e( 'Длительность плавной плавной анимации проявления (Fade-in) рамок при первой загрузке страницы сайта (по умолчанию 1500 мс / 1.5 сек).', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
					</table>
				</div>

				<!-- 2.8 Сдвиг и Окно Превью -->
				<div id="tab-offsets" class="premium-forest-tab-content">
					<h2><?php esc_html_e( '📐 Тонкая настройка сдвигов за экран и Интерактивное Превью', 'premium-forest-parallax' ); ?></h2>
					<p style="font-style: italic; color: #646970; margin-bottom: 25px;">
						<?php esc_html_e( 'Настройте величину сдвига (выхода) рамок наложения за пределы экрана по каждому направлению отдельно. Положительное значение смещает изображение за пределы видимой области, освобождая драгоценное пространство для контента сайта.', 'premium-forest-parallax' ); ?>
					</p>

					<div style="display: flex; gap: 40px; flex-wrap: wrap;">
						<!-- Опции сдвига -->
						<div style="flex: 1; min-width: 320px;">
							<h3 style="border-bottom: 1px solid #ddd; padding-bottom: 8px; color: #51106e;"><?php esc_html_e( '🖥️ Сдвиг за экран (Десктоп/ПК в px)', 'premium-forest-parallax' ); ?></h3>
							<table class="form-table" style="margin-top:0;">
								<tr>
									<th scope="row" style="width:180px;"><?php esc_html_e( 'Сдвиг СЛЕВА', 'premium-forest-parallax' ); ?></th>
									<td>
										<input type="number" id="offset_desktop_l" class="premium-offset-input" name="premium_forest_parallax_settings[desktop_offset_left]" value="<?php echo esc_attr( $settings['desktop_offset_left'] ); ?>" min="0" max="400" />
									</td>
								</tr>
								<tr>
									<th scope="row" style="width:180px;"><?php esc_html_e( 'Сдвиг СПРАВА', 'premium-forest-parallax' ); ?></th>
									<td>
										<input type="number" id="offset_desktop_r" class="premium-offset-input" name="premium_forest_parallax_settings[desktop_offset_right]" value="<?php echo esc_attr( $settings['desktop_offset_right'] ); ?>" min="0" max="400" />
									</td>
								</tr>
								<tr>
									<th scope="row" style="width:180px;"><?php esc_html_e( 'Сдвиг СВЕРХУ', 'premium-forest-parallax' ); ?></th>
									<td>
										<input type="number" id="offset_desktop_t" class="premium-offset-input" name="premium_forest_parallax_settings[desktop_offset_top]" value="<?php echo esc_attr( $settings['desktop_offset_top'] ); ?>" min="0" max="400" />
									</td>
								</tr>
								<tr>
									<th scope="row" style="width:180px;"><?php esc_html_e( 'Сдвиг СНИЗУ', 'premium-forest-parallax' ); ?></th>
									<td>
										<input type="number" id="offset_desktop_b" class="premium-offset-input" name="premium_forest_parallax_settings[desktop_offset_bottom]" value="<?php echo esc_attr( $settings['desktop_offset_bottom'] ); ?>" min="0" max="400" />
									</td>
								</tr>
							</table>

							<h3 style="border-bottom: 1px solid #ddd; padding-bottom: 8px; color: #a42c2c; margin-top:30px;"><?php esc_html_e( '📱 Сдвиг за экран (Мобильные в px)', 'premium-forest-parallax' ); ?></h3>
							<table class="form-table" style="margin-top:0;">
								<tr>
									<th scope="row" style="width:180px;"><?php esc_html_e( 'Сдвиг СЛЕВА', 'premium-forest-parallax' ); ?></th>
									<td>
										<input type="number" id="offset_mobile_l" class="premium-offset-input" name="premium_forest_parallax_settings[mobile_offset_left]" value="<?php echo esc_attr( $settings['mobile_offset_left'] ); ?>" min="0" max="400" />
									</td>
								</tr>
								<tr>
									<th scope="row" style="width:180px;"><?php esc_html_e( 'Сдвиг СПРАВА', 'premium-forest-parallax' ); ?></th>
									<td>
										<input type="number" id="offset_mobile_r" class="premium-offset-input" name="premium_forest_parallax_settings[mobile_offset_right]" value="<?php echo esc_attr( $settings['mobile_offset_right'] ); ?>" min="0" max="400" />
									</td>
								</tr>
								<tr>
									<th scope="row" style="width:180px;"><?php esc_html_e( 'Сдвиг СВЕРХУ', 'premium-forest-parallax' ); ?></th>
									<td>
										<input type="number" id="offset_mobile_t" class="premium-offset-input" name="premium_forest_parallax_settings[mobile_offset_top]" value="<?php echo esc_attr( $settings['mobile_offset_top'] ); ?>" min="0" max="400" />
									</td>
								</tr>
								<tr>
									<th scope="row" style="width:180px;"><?php esc_html_e( 'Сдвиг СНИЗУ', 'premium-forest-parallax' ); ?></th>
									<td>
										<input type="number" id="offset_mobile_b" class="premium-offset-input" name="premium_forest_parallax_settings[mobile_offset_bottom]" value="<?php echo esc_attr( $settings['mobile_offset_bottom'] ); ?>" min="0" max="400" />
									</td>
								</tr>
							</table>
						</div>

						<!-- Окно Интерактивного Превью -->
						<div class="premium-preview-panel" style="flex: 1; min-width: 320px; background: #fafafb; border: 1px solid #ddecde; border-radius: 8px; padding: 25px;">
							<h3 style="margin-top:0; color:#1e3c1a; display:flex; align-items:center; gap:8px;">
								<span>🔍</span> <?php esc_html_e( 'Интерактивный Живой Предпросмотр', 'premium-forest-parallax' ); ?>
							</h3>
							<p class="description" style="margin-bottom:15px;">
								<?php esc_html_e( 'Попробуйте изменить значения сдвигов слева и поменяйте режим предпросмотра!', 'premium-forest-parallax' ); ?>
							</p>

							<div class="preview-mode-switch" style="margin-bottom: 20px;">
								<button type="button" class="button button-secondary active" id="btn-preview-desktop"><?php esc_html_e( '💻 Десктопный вид', 'premium-forest-parallax' ); ?></button>
								<button type="button" class="button button-secondary" id="btn-preview-mobile"><?php esc_html_e( '📱 Мобильный вид', 'premium-forest-parallax' ); ?></button>
							</div>

							<!-- Эмулятор Браузера -->
							<div id="premium-preview-simulator" class="desktop-mode" style="position:relative; width:100%; height:320px; background:#fff; border:2px solid #ccc; border-radius:6px; overflow:hidden; box-shadow:inset 0 0 15px rgba(0,0,0,0.05); margin: 0 auto; transition: all 0.3s ease;">
								<div class="sim-edge sim-left" style="position:absolute; top:0; left:0; width:45px; height:100%; background:linear-gradient(90deg, #3d6a24, #2f541c); border-right:2px dashed #1b320f; opacity: 0.85; transition: all 0.2s ease;"></div>
								<div class="sim-edge sim-right" style="position:absolute; top:0; right:0; width:45px; height:100%; background:linear-gradient(270deg, #3d6a24, #2f541c); border-left:2px dashed #1b320f; opacity: 0.85; transition: all 0.2s ease;"></div>
								<div class="sim-edge sim-top" style="position:absolute; top:0; left:0; width:100%; height:35px; background:linear-gradient(180deg, #3d6a24, #2f541c); border-bottom:2px dashed #1b320f; opacity: 0.85; transition: all 0.2s ease;"></div>
								<div class="sim-edge sim-bottom" style="position:absolute; bottom:0; left:0; width:100%; height:35px; background:linear-gradient(0deg, #3d6a24, #2f541c); border-top:2px dashed #1b320f; opacity: 0.85; transition: all 0.2s ease;"></div>

								<div class="sim-content" style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; text-align:center; padding: 20px; box-sizing:border-box; color:#666;">
									<strong style="color:#111;"><?php esc_html_e( 'Ваш Сайт (Контент)', 'premium-forest-parallax' ); ?></strong>
									<span style="font-size:11px; margin-top:5px;"><?php esc_html_e( 'Зеленым показаны лесные рамки по краям.', 'premium-forest-parallax' ); ?></span>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- 3. Ветер -->
				<div id="tab-wind" class="premium-forest-tab-content">
					<h2><?php esc_html_e( 'Математическая симуляция ветра (Шум Перлина)', 'premium-forest-parallax' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Направление ветра (градусы)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[wind_direction]" value="<?php echo esc_attr( $settings['wind_direction'] ); ?>" min="0" max="360" />
								<span class="premium-forest-desc"><?php esc_html_e( 'Угол направления ветра (от 0 до 360 градусов). По умолчанию 180 (ветер дует справа налево).', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Сила ветра', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[wind_strength]" value="<?php echo esc_attr( $settings['wind_strength'] ); ?>" min="0" max="50" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Порывы ветра (Gusts)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[wind_gusts]" value="<?php echo esc_attr( $settings['wind_gusts'] ); ?>" min="0" max="10" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Турбулентность (Случайность)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[wind_randomness]" value="<?php echo esc_attr( $settings['wind_randomness'] ); ?>" min="0" max="20" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Частота шума Перлина', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[wind_frequency]" value="<?php echo esc_attr( $settings['wind_frequency'] ); ?>" min="1" max="10" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Вращение листьев при колыхании', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[wind_rotation]" value="<?php echo esc_attr( $settings['wind_rotation'] ); ?>" min="0" max="50" />
							</td>
						</tr>
					</table>
				</div>

				<!-- 4. Частицы -->
				<div id="tab-particles" class="premium-forest-tab-content">
					<h2><?php esc_html_e( 'Атмосферная пыльца и светящиеся микрочастицы', 'premium-forest-parallax' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Включить летающие частицы', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[particles_enabled]" value="1" <?php checked( $settings['particles_enabled'], '1' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Тип взвеси частиц', 'premium-forest-parallax' ); ?></th>
							<td>
								<select name="premium_forest_parallax_settings[particles_type]">
									<option value="pollen" <?php selected( $settings['particles_type'], 'pollen' ); ?>><?php esc_html_e( 'Лесная цветочная пыльца', 'premium-forest-parallax' ); ?></option>
									<option value="dust" <?php selected( $settings['particles_type'], 'dust' ); ?>><?php esc_html_e( 'Атмосферная солнечная пыль', 'premium-forest-parallax' ); ?></option>
									<option value="small" <?php selected( $settings['particles_type'], 'small' ); ?>><?php esc_html_e( 'Мелкие кусочки листьев и коры', 'premium-forest-parallax' ); ?></option>
									<option value="glowing" <?php selected( $settings['particles_type'], 'glowing' ); ?>><?php esc_html_e( 'Светящиеся лесные светлячки', 'premium-forest-parallax' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Максимальное количество', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[particles_count]" value="<?php echo esc_attr( $settings['particles_count'] ); ?>" min="10" max="500" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Средний размер частиц (px)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[particles_size]" value="<?php echo esc_attr( $settings['particles_size'] ); ?>" min="1" max="20" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Скорость движения частиц', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[particles_speed]" value="<?php echo esc_attr( $settings['particles_speed'] ); ?>" min="1" max="10" />
							</td>
						</tr>
					</table>
				</div>

				<!-- 5. Освещение -->
				<div id="tab-lighting" class="premium-forest-tab-content">
					<h2><?php esc_html_e( 'Пост-обработка: Освещение и визуальные шейдеры', 'premium-forest-parallax' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Объемные солнечные лучи (God Rays)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[light_god_rays]" value="1" <?php checked( $settings['light_god_rays'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Проецирует мягкие трехмерные световые лучи сквозь ветви деревьев.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Размытие свечения (Bloom Pass)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[light_bloom]" value="1" <?php checked( $settings['light_bloom'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Придает ярким точкам и летающей пыльце благородное мягкое свечение.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Атмосферное сияние солнца (Sun Glow)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[light_sun_glow]" value="1" <?php checked( $settings['light_sun_glow'], '1' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Блики линзы объектива (Lens Flare)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[light_lens_flare]" value="1" <?php checked( $settings['light_lens_flare'], '1' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row--------------"><?php esc_html_e( 'Мягкий рассеянный свет (Soft Ambient)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[light_soft_light]" value="1" <?php checked( $settings['light_soft_light'], '1' ); ?> />
							</td>
						</tr>
					</table>
				</div>

				<!-- 6. Туман -->
				<div id="tab-fog" class="premium-forest-tab-content">
					<h2><?php esc_html_e( 'Атмосферный и глубинный туман', 'premium-forest-parallax' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Активировать глубинный туман', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[fog_enabled]" value="1" <?php checked( $settings['fog_enabled'], '1' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Разделение тумана по слоям (Depth)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[fog_depth]" value="1" <?php checked( $settings['fog_depth'], '1' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Дополнительное размытие тумана', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[fog_atmospheric]" value="1" <?php checked( $settings['fog_atmospheric'], '1' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Интенсивность размытия тумана (px)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[fog_blur]" value="<?php echo esc_attr( $settings['fog_blur'] ); ?>" min="0" max="50" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Дальность видимости тумана', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[fog_distance]" value="<?php echo esc_attr( $settings['fog_distance'] ); ?>" min="10" max="200" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Плотность тумана (%)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[fog_opacity]" value="<?php echo esc_attr( $settings['fog_opacity'] ); ?>" min="0" max="100" />
							</td>
						</tr>
					</table>
				</div>

				<!-- 7. Параллакс -->
				<div id="tab-parallax" class="premium-forest-tab-content">
					<h2><?php esc_html_e( 'Параллакс-слои и кинематика', 'premium-forest-parallax' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Количество слоев (Минимум 7)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[parallax_layers]" value="<?php echo esc_attr( $settings['parallax_layers'] ); ?>" min="7" max="15" />
								<span class="premium-forest-desc"><?php esc_html_e( 'Для глубокого кинематографического эффекта по краям используется ровно 7 (или более) независимых слоев веток.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Множитель скорости параллакса', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[parallax_speed]" value="<?php echo esc_attr( $settings['parallax_speed'] ); ?>" min="1" max="20" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Глубина сцены (Perceived Depth)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[parallax_depth]" value="<?php echo esc_attr( $settings['parallax_depth'] ); ?>" min="1" max="10" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Инерция мыши (Сглаживание)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[parallax_inertia]" value="<?php echo esc_attr( $settings['parallax_inertia'] ); ?>" min="1" max="20" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Сглаживание скроллинга страницы', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[parallax_smooth]" value="1" <?php checked( $settings['parallax_smooth'], '1' ); ?> />
							</td>
						</tr>
					</table>
				</div>

				<!-- 8. WebGL2 -->
				<div id="tab-webgl" class="premium-forest-tab-content">
					<h2><?php esc_html_e( 'Параметры 3D-ускорения WebGL2 и Three.js', 'premium-forest-parallax' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Использовать аппаратный WebGL2', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[webgl_enabled]" value="1" <?php checked( $settings['webgl_enabled'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'При включении плагин задействует видеокарту устройства для рендеринга со скоростью 60 FPS. При отключении будет использован Canvas2D-рендерер.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Шейдер свечения (Bloom Shader Pass)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[webgl_bloom_pass]" value="1" <?php checked( $settings['webgl_bloom_pass'], '1' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Шейдер размытия (Gaussian Blur Pass)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[webgl_blur_pass]" value="1" <?php checked( $settings['webgl_blur_pass'], '1' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Шейдер кинозернистости (Noise Pass)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[webgl_noise_pass]" value="1" <?php checked( $settings['webgl_noise_pass'], '1' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Шейдер слоистого тумана (Fog Pass)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[webgl_fog_pass]" value="1" <?php checked( $settings['webgl_fog_pass'], '1' ); ?> />
							</td>
						</tr>
					</table>
				</div>

				<!-- 9. Оптимизация -->
				<div id="tab-performance" class="premium-forest-tab-content">
					<h2><?php esc_html_e( 'Интеллектуальная оптимизация и профили производительности', 'premium-forest-parallax' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Использовать лимитер кадров (FPS Limiter)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[perf_limit_fps]" value="1" <?php checked( $settings['perf_limit_fps'], '1' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Целевой порог FPS', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[perf_target_fps]" value="<?php echo esc_attr( $settings['perf_target_fps'] ); ?>" min="15" max="120" />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Автоматическое тестирование устройства (Benchmark)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[perf_observe]" value="1" <?php checked( $settings['perf_observe'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Замеряет скорость CPU и GPU при загрузке страницы, автоматически переключая эффекты между профилями Ultra, High, Medium, Low и Lite на любых мобильных телефонах и слабых ПК.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Ставить на паузу при неактивной вкладке', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[perf_pause_hidden]" value="1" <?php checked( $settings['perf_pause_hidden'], '1' ); ?> />
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Останавливать за экраном (IntersectionObserver)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[perf_pause_offscr]" value="1" <?php checked( $settings['perf_pause_offscr'], '1' ); ?> />
							</td>
						</tr>
					</table>
				</div>

				<!-- 9.5 Мобильные устройства -->
				<div id="tab-mobile" class="premium-forest-tab-content">
					<h2><?php esc_html_e( 'Специфичные настройки для мобильных устройств (смартфоны и планшеты)', 'premium-forest-parallax' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Включить мобильную оптимизацию', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[mobile_optimize_enabled]" value="1" <?php checked( $settings['mobile_optimize_enabled'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Включает автоматическое интеллектуальное сжатие и адаптацию для сенсорных экранов.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Отключить тяжелые шейдеры (Bloom/Blur)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[mobile_disable_effects]" value="1" <?php checked( $settings['mobile_disable_effects'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Отключает ресурсоемкие шейдеры размытия и свечения на мобильных для экономии заряда.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Принудительный Canvas2D (без WebGL)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[mobile_force_canvas2d]" value="1" <?php checked( $settings['mobile_force_canvas2d'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Всегда использовать высокопроизводительный Canvas2D-рендерер вместо трехмерного WebGL2 на смартфонах.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Сократить листья на (%)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[mobile_reduce_leaves]" value="<?php echo esc_attr( $settings['mobile_reduce_leaves'] ); ?>" min="0" max="95" />
								<span class="premium-forest-desc"><?php esc_html_e( 'На сколько процентов уменьшить число летающих листьев на смартфонах (по умолчанию 50%).', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Сократить микрочастицы на (%)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[mobile_reduce_particles]" value="<?php echo esc_attr( $settings['mobile_reduce_particles'] ); ?>" min="0" max="95" />
								<span class="premium-forest-desc"><?php esc_html_e( 'На сколько процентов снизить плотность летающей пыльцы.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Сократить густоту ветвей на (%)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[mobile_reduce_branches]" value="<?php echo esc_attr( $settings['mobile_reduce_branches'] ); ?>" min="0" max="95" />
								<span class="premium-forest-desc"><?php esc_html_e( 'На сколько процентов уменьшить ветвистость лесного края по бокам.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Отключить гироскоп (акселерометр)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[mobile_disable_gyro]" value="1" <?php checked( $settings['mobile_disable_gyro'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Отключает отслеживание физического наклона телефона для экономии батареи (будет работать только тач).', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Полностью скрыть на мобильных', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[mobile_disable_on_touch]" value="1" <?php checked( $settings['mobile_disable_on_touch'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Если включено, лесной параллакс не будет вообще отображаться на смартфонах и планшетах.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Исчезновение при нажатии (UX)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="checkbox" name="premium_forest_parallax_settings[mobile_fade_on_tap]" value="1" <?php checked( $settings['mobile_fade_on_tap'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Включает плавное исчезновение леса при любом таче по экрану смартфона, чтобы не мешать просмотру контента.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Длительность исчезновения (мс)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[mobile_fade_out_duration]" value="<?php echo esc_attr( $settings['mobile_fade_out_duration'] ); ?>" min="100" max="5000" />
								<span class="premium-forest-desc"><?php esc_html_e( 'Сколько миллисекунд длится плавное растворение рамок и листьев (по умолчанию 800 мс).', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Время отсутствия активности перед возвратом (мс)', 'premium-forest-parallax' ); ?></th>
							<td>
								<input type="number" name="premium_forest_parallax_settings[mobile_reappear_delay]" value="<?php echo esc_attr( $settings['mobile_reappear_delay'] ); ?>" min="1000" max="30000" />
								<span class="premium-forest-desc"><?php esc_html_e( 'Время бездействия в миллисекундах перед тем, как лесной эффект плавно появится снова (по умолчанию 4000 мс / 4 сек).', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row" style="color: #104c6e; background: #f0f9ff;"><?php esc_html_e( 'Скрыть Рамки-Наложения на мобильных', 'premium-forest-parallax' ); ?></th>
							<td style="background: #f0f9ff;">
								<input type="checkbox" name="premium_forest_parallax_settings[mobile_overlay_hide]" value="1" <?php checked( $settings['mobile_overlay_hide'], '1' ); ?> />
								<span class="premium-forest-desc"><?php esc_html_e( 'Полностью отключить отображение рамок-наложений на экранах смартфонов.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row" style="color: #104c6e; background: #f0f9ff;"><?php esc_html_e( 'Ширина рамок на мобильных (px)', 'premium-forest-parallax' ); ?></th>
							<td style="background: #f0f9ff;">
								<input type="number" name="premium_forest_parallax_settings[mobile_overlay_width]" value="<?php echo esc_attr( $settings['mobile_overlay_width'] ); ?>" min="20" max="500" />
								<span class="premium-forest-desc"><?php esc_html_e( 'Уменьшите размер рамок на мобильных (например, до 150px вместо 250px на десктопе), чтобы они не перекрывали контент.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row" style="color: #104c6e; background: #f0f9ff;"><?php esc_html_e( 'Прозрачность рамок на мобильных (%)', 'premium-forest-parallax' ); ?></th>
							<td style="background: #f0f9ff;">
								<input type="number" name="premium_forest_parallax_settings[mobile_overlay_opacity]" value="<?php echo esc_attr( $settings['mobile_overlay_opacity'] ); ?>" min="10" max="100" />
							</td>
						</tr>
						<tr>
							<th scope="row" style="color: #104c6e; background: #f0f9ff;"><?php esc_html_e( 'Сила параллакса рамок на мобильных', 'premium-forest-parallax' ); ?></th>
							<td style="background: #f0f9ff;">
								<input type="number" name="premium_forest_parallax_settings[mobile_overlay_parallax_strength]" value="<?php echo esc_attr( $settings['mobile_overlay_parallax_strength'] ); ?>" min="0" max="30" />
								<span class="premium-forest-desc"><?php esc_html_e( 'Укажите 0, чтобы полностью зафиксировать картинку-рамку на мобильных девайсах без ее смещения.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
					</table>
				</div>

				<!-- 10. Кастомный код -->
				<div id="tab-advanced" class="premium-forest-tab-content">
					<h2><?php esc_html_e( 'Кастомный CSS и JS код разработчика', 'premium-forest-parallax' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Пользовательские стили CSS', 'premium-forest-parallax' ); ?></th>
							<td>
								<textarea name="premium_forest_parallax_settings[custom_css]" rows="6"><?php echo esc_textarea( $settings['custom_css'] ); ?></textarea>
								<span class="premium-forest-desc"><?php esc_html_e( 'Добавьте кастомный CSS-код. Стили будут автоматически внедрены в подвал сайта.', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Пользовательский JS-скрипт (Callback)', 'premium-forest-parallax' ); ?></th>
							<td>
								<textarea name="premium_forest_parallax_settings[custom_js]" rows="6"><?php echo esc_textarea( $settings['custom_js'] ); ?></textarea>
								<span class="premium-forest-desc"><?php esc_html_e( 'JavaScript-код, который сработает при инициализации WebGL-сцены. Доступны аргументы (physics, renderer).', 'premium-forest-parallax' ); ?></span>
							</td>
						</tr>
					</table>
				</div>

				<!-- 11. Справка -->
				<div id="tab-help" class="premium-forest-tab-content">
					<h2 style="color:#b77a00; border-bottom-color:#ffe4a0;"><?php esc_html_e( '📚 Расширенная справка и руководство пользователя', 'premium-forest-parallax' ); ?></h2>

					<div style="background: #fffcf0; padding: 20px; border: 1px solid #ffe4a0; border-radius: 6px; line-height: 1.6; font-size: 14px; color: #3c3010;">
						<h3 style="margin-top:0; color:#b77a00; font-size: 18px;">Как работает плагин Premium Forest Parallax?</h3>
						<p>Плагин создает премиальный визуальный эффект «живого леса» строго по краям экрана. Центральная область сайта остается <strong>абсолютно пустой, прозрачной и кликабельной</strong>. Эффект работает автоматически на любой WordPress-теме и поддерживает конструктор Elementor.</p>

						<h3 style="color:#b77a00; font-size: 16px; margin-top:20px;">Оптимизация для ЛЮБЫХ устройств:</h3>
						<ul style="list-style-type: disc; margin-left: 20px; margin-bottom: 20px;">
							<li><strong>Смартфоны и планшеты:</strong> Плагин автоматически определяет тип девайса и задействует облегчённый рендеринг: отключает тяжелые полноэкранные эффекты свечения (Bloom) и размытия (Blur), уменьшает количество летающих листьев на 50% и переключает рендеринг на экономичный Canvas2D для сохранения заряда батареи и плавности работы.</li>
							<li><strong>Retina-дисплеи:</strong> Автоматически увеличивает плотность пикселей эффектов, сохраняя кристальную четкость векторных листьев на дисплеях высокого разрешения.</li>
							<li><strong>Спящий режим (CPU/GPU Throttle):</strong> С помощью встроенного <code>IntersectionObserver</code> и слежения за изменением видимости вкладок браузера, плагин ставит рендеринг на 100% паузу, как только пользователь прокручивает страницу вниз или переходит на другую вкладку.</li>
						</ul>

						<h3 style="color:#b77a00; font-size: 16px;">Математическая физика ветра и листьев:</h3>
						<ul style="list-style-type: disc; margin-left: 20px; margin-bottom: 20px;">
							<li><strong>Шум Перлина (Perlin Noise):</strong> В отличие от простых синусоидальных колебаний, ветер рассчитывается по математической формуле шума Перлина. Колыхание листьев выглядит естественно и не имеет цикличных повторений.</li>
							<li><strong>Физика падения:</strong> Каждый лист рассчитывается как индивидуальный трехмерный твердотельный объект, имеющий массу, гравитацию, парусность (Drag), крутящий момент (Angular Velocity) и реакцию на положение курсора.</li>
						</ul>

						<h3 style="color:#b77a00; font-size: 16px;">Реакция на действия пользователя:</h3>
						<p>Слои плавно смещаются при движении мыши (на ПК) или при изменении пространственного наклона телефона по встроенному <strong>гироскопу</strong> (функция Device Orientation на мобильных), создавая завораживающий эффект глубины.</p>
					</div>
				</div>

			</div>
		</div>

		<div class="premium-forest-footer">
			<?php submit_button( __( 'Сохранить настройки премиум-леса', 'premium-forest-parallax' ), 'primary', 'submit', false ); ?>
		</div>
	</form>
</div>
