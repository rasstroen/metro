-- phpMyAdmin SQL Dump
-- version 3.3.10
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Янв 12 2012 г., 18:52
-- Версия сервера: 5.0.92
-- Версия PHP: 5.3.8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- База данных: `metro`
--

-- --------------------------------------------------------

--
-- Структура таблицы `metro_lines`
--

CREATE TABLE IF NOT EXISTS `metro_lines` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=13 ;

--
-- Дамп данных таблицы `metro_lines`
--

INSERT INTO `metro_lines` (`id`, `title`) VALUES
(1, 'Сокольническая'),
(2, 'Замоскворецкая'),
(3, 'Арбатско-Покровская'),
(4, 'Филевская'),
(5, 'Кольцевая'),
(6, 'Калужско-Рижская'),
(7, 'Таганско-Краснопресненская'),
(8, 'Калининская'),
(9, 'Серпуховско-Тимирязевская'),
(10, 'Люблинско-Дмитровская'),
(11, 'Каховская'),
(12, 'Бутовская');

-- --------------------------------------------------------

--
-- Структура таблицы `metro_stations`
--

CREATE TABLE IF NOT EXISTS `metro_stations` (
  `id` int(11) NOT NULL auto_increment,
  `id_line` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `lat` decimal(10,6) NOT NULL,
  `lon` decimal(10,6) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=188 ;

--
-- Дамп данных таблицы `metro_stations`
--

INSERT INTO `metro_stations` (`id`, `id_line`, `title`, `lat`, `lon`) VALUES
(1, 1, 'Улица Подбельского', 0.000000, 0.000000),
(2, 1, 'Черкизовская', 55.803844, 37.744694),
(3, 1, 'Преображенская площадь', 55.796096, 37.715598),
(4, 1, 'Сокольники', 0.000000, 0.000000),
(5, 1, 'Красносельская', 0.000000, 0.000000),
(6, 1, 'Комсомольская', 0.000000, 0.000000),
(7, 1, 'Красные ворота', 0.000000, 0.000000),
(8, 1, 'Чистые пруды', 0.000000, 0.000000),
(9, 1, 'Лубянка', 0.000000, 0.000000),
(10, 1, 'Охотный ряд', 0.000000, 0.000000),
(11, 1, 'Библиотека им. Ленина', 0.000000, 0.000000),
(12, 1, 'Кропоткинская', 0.000000, 0.000000),
(13, 1, 'Парк культуры', 0.000000, 0.000000),
(14, 1, 'Фрунзенская', 0.000000, 0.000000),
(15, 1, 'Спортивная', 0.000000, 0.000000),
(16, 1, 'Воробьёвы горы', 0.000000, 0.000000),
(17, 1, 'Университет', 0.000000, 0.000000),
(18, 1, 'Проспект Вернадского', 0.000000, 0.000000),
(19, 1, 'Юго-Западная', 0.000000, 0.000000),
(20, 2, 'Красногвардейская', 0.000000, 0.000000),
(21, 2, 'Домодедовская', 0.000000, 0.000000),
(22, 2, 'Орехово', 0.000000, 0.000000),
(23, 2, 'Царицыно', 0.000000, 0.000000),
(24, 2, 'Кантемировская', 0.000000, 0.000000),
(25, 2, 'Каширская', 0.000000, 0.000000),
(26, 2, 'Коломенская', 0.000000, 0.000000),
(27, 2, 'Автозаводская', 0.000000, 0.000000),
(28, 2, 'Павелецкая', 0.000000, 0.000000),
(29, 2, 'Новокузнецкая', 55.742418, 37.629268),
(30, 2, 'Театральная', 0.000000, 0.000000),
(31, 2, 'Тверская', 55.765116, 37.605143),
(32, 2, 'Маяковская', 0.000000, 0.000000),
(33, 2, 'Белорусская', 0.000000, 0.000000),
(34, 2, 'Динамо', 0.000000, 0.000000),
(35, 2, 'Аэропорт', 0.000000, 0.000000),
(36, 2, 'Сокол', 0.000000, 0.000000),
(37, 2, 'Войковская', 0.000000, 0.000000),
(38, 2, 'Водный стадион', 0.000000, 0.000000),
(39, 2, 'Речной вокзал', 0.000000, 0.000000),
(40, 3, 'Митино', 0.000000, 0.000000),
(41, 3, 'Волоколамская', 0.000000, 0.000000),
(42, 3, 'Мякинино', 0.000000, 0.000000),
(43, 3, 'Строгино', 0.000000, 0.000000),
(44, 3, 'Крылатское', 0.000000, 0.000000),
(45, 3, 'Молодежная', 0.000000, 0.000000),
(46, 3, 'Кунцевская', 0.000000, 0.000000),
(47, 3, 'Славянский бульвар', 0.000000, 0.000000),
(48, 3, 'Парк Победы', 0.000000, 0.000000),
(49, 3, 'Киевская', 0.000000, 0.000000),
(50, 3, 'Смоленская', 0.000000, 0.000000),
(51, 3, 'Арбатская', 0.000000, 0.000000),
(52, 3, 'Площадь Революции', 0.000000, 0.000000),
(53, 3, 'Курская', 0.000000, 0.000000),
(54, 3, 'Бауманская', 0.000000, 0.000000),
(55, 3, 'Электрозаводская', 0.000000, 0.000000),
(56, 3, 'Семеновская', 0.000000, 0.000000),
(57, 3, 'Партизанская', 0.000000, 0.000000),
(58, 3, 'Измайловская', 0.000000, 0.000000),
(59, 3, 'Первомайская', 0.000000, 0.000000),
(60, 3, 'Щелковская', 55.809597, 37.798617),
(61, 4, 'Александровский сад', 0.000000, 0.000000),
(62, 4, 'Арбатская', 0.000000, 0.000000),
(63, 4, 'Смоленская', 0.000000, 0.000000),
(64, 4, 'Киевская', 0.000000, 0.000000),
(65, 4, 'Выставочная', 0.000000, 0.000000),
(66, 4, 'Международная', 0.000000, 0.000000),
(67, 4, 'Студенческая', 0.000000, 0.000000),
(68, 4, 'Кутузовская', 0.000000, 0.000000),
(69, 4, 'Фили', 0.000000, 0.000000),
(70, 4, 'Багратионовская', 0.000000, 0.000000),
(71, 4, 'Филевский парк', 0.000000, 0.000000),
(72, 4, 'Пионерская', 0.000000, 0.000000),
(73, 4, 'Кунцевская', 0.000000, 0.000000),
(74, 5, 'Белорусская', 0.000000, 0.000000),
(75, 5, 'Новослободская', 0.000000, 0.000000),
(76, 5, 'Проспект Мира', 0.000000, 0.000000),
(77, 5, 'Комсомольская', 0.000000, 0.000000),
(78, 5, 'Курская', 0.000000, 0.000000),
(79, 5, 'Таганская', 0.000000, 0.000000),
(80, 5, 'Павелецкая', 0.000000, 0.000000),
(81, 5, 'Добрынинская', 0.000000, 0.000000),
(82, 5, 'Октябрьская', 0.000000, 0.000000),
(83, 5, 'Парк культуры', 0.000000, 0.000000),
(84, 5, 'Киевская', 0.000000, 0.000000),
(85, 5, 'Краснопресненская', 0.000000, 0.000000),
(86, 6, 'Медведково', 0.000000, 0.000000),
(87, 6, 'Бабушкинская', 0.000000, 0.000000),
(88, 6, 'Свиблово', 0.000000, 0.000000),
(89, 6, 'Ботанический сад', 0.000000, 0.000000),
(90, 6, 'ВДНХ', 0.000000, 0.000000),
(91, 6, 'Алексеевская', 0.000000, 0.000000),
(92, 6, 'Рижская', 0.000000, 0.000000),
(93, 6, 'Проспект Мира', 0.000000, 0.000000),
(94, 6, 'Сухаревская', 0.000000, 0.000000),
(95, 6, 'Тургеневская', 0.000000, 0.000000),
(96, 6, 'Китай-город', 0.000000, 0.000000),
(97, 6, 'Третьяковская', 0.000000, 0.000000),
(98, 6, 'Октябрьская', 0.000000, 0.000000),
(99, 6, 'Шаболовская', 0.000000, 0.000000),
(100, 6, 'Ленинский проспект', 0.000000, 0.000000),
(101, 6, 'Академическая', 0.000000, 0.000000),
(102, 6, 'Профсоюзная', 0.000000, 0.000000),
(103, 6, 'Новые Черемушки', 0.000000, 0.000000),
(104, 6, 'Калужская', 0.000000, 0.000000),
(105, 6, 'Беляево', 0.000000, 0.000000),
(106, 6, 'Коньково', 0.000000, 0.000000),
(107, 6, 'Теплый Стан', 0.000000, 0.000000),
(108, 6, 'Ясенево', 0.000000, 0.000000),
(109, 6, 'Новоясеневская', 0.000000, 0.000000),
(110, 7, 'Выхино', 0.000000, 0.000000),
(111, 7, 'Рязанский проспект', 0.000000, 0.000000),
(112, 7, 'Кузьминки', 0.000000, 0.000000),
(113, 7, 'Текстильщики', 0.000000, 0.000000),
(114, 7, 'Волгоградский проспект', 0.000000, 0.000000),
(115, 7, 'Пролетарская', 0.000000, 0.000000),
(116, 7, 'Таганская', 0.000000, 0.000000),
(117, 7, 'Китай-город', 0.000000, 0.000000),
(118, 7, 'Кузнецкий мост', 0.000000, 0.000000),
(119, 7, 'Пушкинская', 55.765956, 37.604165),
(120, 7, 'Баррикадная', 0.000000, 0.000000),
(121, 7, 'Улица 1905 года', 0.000000, 0.000000),
(122, 7, 'Беговая', 0.000000, 0.000000),
(123, 7, 'Полежаевская', 0.000000, 0.000000),
(124, 7, 'Октябрьское поле', 0.000000, 0.000000),
(125, 7, 'Щукинская', 0.000000, 0.000000),
(126, 7, 'Тушинская', 0.000000, 0.000000),
(127, 7, 'Сходненская', 0.000000, 0.000000),
(128, 7, 'Планерная', 0.000000, 0.000000),
(129, 8, 'Новогиреево', 0.000000, 0.000000),
(130, 8, 'Перово', 0.000000, 0.000000),
(131, 8, 'Шоссе Энтузиастов', 0.000000, 0.000000),
(132, 8, 'Авиамоторная', 0.000000, 0.000000),
(133, 8, 'Площадь Ильича', 0.000000, 0.000000),
(134, 8, 'Марксистская', 0.000000, 0.000000),
(135, 8, 'Третьяковская', 0.000000, 0.000000),
(136, 9, 'Алтуфьево', 0.000000, 0.000000),
(137, 9, 'Бибирево', 0.000000, 0.000000),
(138, 9, 'Отрадное', 0.000000, 0.000000),
(139, 9, 'Владыкино', 0.000000, 0.000000),
(140, 9, 'Петровско-Разумовская', 0.000000, 0.000000),
(141, 9, 'Тимирязевская', 0.000000, 0.000000),
(142, 9, 'Дмитровская', 0.000000, 0.000000),
(143, 9, 'Савеловская', 0.000000, 0.000000),
(144, 9, 'Менделеевская', 0.000000, 0.000000),
(145, 9, 'Цветной бульвар', 0.000000, 0.000000),
(146, 9, 'Чеховская', 0.000000, 0.000000),
(147, 9, 'Боровицкая', 0.000000, 0.000000),
(148, 9, 'Полянка', 0.000000, 0.000000),
(149, 9, 'Серпуховская', 0.000000, 0.000000),
(150, 9, 'Тульская', 0.000000, 0.000000),
(151, 9, 'Нагатинская', 0.000000, 0.000000),
(152, 9, 'Нагорная', 0.000000, 0.000000),
(153, 9, 'Нахимовский проспект', 0.000000, 0.000000),
(154, 9, 'Севастопольская', 0.000000, 0.000000),
(155, 9, 'Чертановская', 0.000000, 0.000000),
(156, 9, 'Южная', 0.000000, 0.000000),
(157, 9, 'Пражская', 0.000000, 0.000000),
(158, 9, 'Улица Академика Янгеля', 0.000000, 0.000000),
(159, 9, 'Аннино', 0.000000, 0.000000),
(160, 9, 'Бульвар Дмитрия Донского', 0.000000, 0.000000),
(161, 10, 'Марьина Роща', 0.000000, 0.000000),
(162, 10, 'Достоевская', 0.000000, 0.000000),
(163, 10, 'Трубная', 0.000000, 0.000000),
(164, 10, 'Сретенский бульвар', 0.000000, 0.000000),
(165, 10, 'Чкаловская', 0.000000, 0.000000),
(166, 10, 'Римская', 0.000000, 0.000000),
(167, 10, 'Крестьянская застава', 0.000000, 0.000000),
(168, 10, 'Дубровка', 0.000000, 0.000000),
(169, 10, 'Кожуховская', 0.000000, 0.000000),
(170, 10, 'Печатники', 0.000000, 0.000000),
(171, 10, 'Волжская', 0.000000, 0.000000),
(172, 10, 'Люблино', 0.000000, 0.000000),
(173, 10, 'Братиславская', 0.000000, 0.000000),
(174, 10, 'Марьино', 0.000000, 0.000000),
(175, 10, 'Борисово', 0.000000, 0.000000),
(176, 10, 'Шипиловская', 0.000000, 0.000000),
(177, 10, 'Зябликово', 0.000000, 0.000000),
(178, 11, 'Каширская', 0.000000, 0.000000),
(179, 11, 'Варшавская', 0.000000, 0.000000),
(180, 11, 'Каховская', 0.000000, 0.000000),
(181, 12, 'Битцевский парк', 0.000000, 0.000000),
(182, 12, 'Лесопарковая', 0.000000, 0.000000),
(183, 12, 'Улица Старокачаловская', 0.000000, 0.000000),
(184, 12, 'Улица Скобелевская', 0.000000, 0.000000),
(185, 12, 'Бульвар адмирала Ушакова', 0.000000, 0.000000),
(186, 12, 'Улица Горчакова', 0.000000, 0.000000),
(187, 12, 'Бунинская аллея', 0.000000, 0.000000);

-- --------------------------------------------------------

--
-- Структура таблицы `stat_user_partner_referer`
--

CREATE TABLE IF NOT EXISTS `stat_user_partner_referer` (
  `id_user` int(10) unsigned NOT NULL default '0',
  `id_partner` int(10) unsigned NOT NULL default '0',
  `time` int(10) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id_user`),
  KEY `id_partner` (`id_partner`),
  KEY `time` (`time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `stat_user_partner_referer`
--

INSERT INTO `stat_user_partner_referer` (`id_user`, `id_partner`, `time`) VALUES
(2, 0, 1326369494);

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `email` varchar(255) NOT NULL default '',
  `password` varchar(40) NOT NULL default '',
  `nickname` varchar(255) NOT NULL default '',
  `hash` varchar(32) NOT NULL default '',
  `role` tinyint(3) unsigned NOT NULL,
  `regTime` int(10) unsigned NOT NULL,
  `regIp` varchar(40) NOT NULL,
  `lastSave` int(11) NOT NULL,
  `counters` varchar(20) NOT NULL,
  `lastIp` varchar(40) NOT NULL,
  `lastLogin` int(10) unsigned NOT NULL,
  `notify_rules` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `nickname` (`nickname`),
  KEY `role` (`role`),
  KEY `regTime` (`regTime`),
  KEY `lastSave` (`lastSave`),
  KEY `lastLogin` (`lastLogin`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `nickname`, `hash`, `role`, `regTime`, `regIp`, `lastSave`, `counters`, `lastIp`, `lastLogin`, `notify_rules`) VALUES
(2, 'amuhc@yandex.ru', 'cca47b4a5300169cd21659ed39165f24', 'rasstroen', '', 30, 1326369494, '194.67.52.194', 1326371401, '0,0,0,0,0', '194.67.52.194', 1326371401, 2425);

-- --------------------------------------------------------

--
-- Структура таблицы `users_messages`
--

CREATE TABLE IF NOT EXISTS `users_messages` (
  `id` int(11) NOT NULL auto_increment,
  `id_author` int(10) unsigned NOT NULL,
  `time` int(10) unsigned NOT NULL,
  `subject` varchar(255) NOT NULL,
  `html` text,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=287 ;

--
-- Дамп данных таблицы `users_messages`
--


-- --------------------------------------------------------

--
-- Структура таблицы `users_messages_index`
--

CREATE TABLE IF NOT EXISTS `users_messages_index` (
  `message_id` int(11) NOT NULL auto_increment,
  `thread_id` int(11) NOT NULL default '0',
  `id_recipient` int(11) NOT NULL default '0',
  `is_new` tinyint(4) NOT NULL default '0',
  `is_deleted` tinyint(4) NOT NULL default '0',
  `type` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`message_id`,`id_recipient`),
  KEY `type` (`type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=287 ;

--
-- Дамп данных таблицы `users_messages_index`
--


-- --------------------------------------------------------

--
-- Структура таблицы `users_session`
--

CREATE TABLE IF NOT EXISTS `users_session` (
  `user_id` int(11) NOT NULL,
  `session` varchar(40) NOT NULL,
  `expires` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`user_id`),
  KEY `expires` (`expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Дамп данных таблицы `users_session`
--

INSERT INTO `users_session` (`user_id`, `session`, `expires`) VALUES
(2, '47e2e8fef8ab84ad711d8fea96571df6', 1326731401);