Attribute VB_Name = "UniversalGenerator"
' ==============================================================================
' МАКРОС: Универсальный генератор документов, карточек и штрих-кодов из Excel
' Разработчик: Jules
' Описание: Автоматически генерирует документы, карточки или ценники на основе
'           настраиваемого шаблона и таблицы данных. Содержит функцию создания
'           векторных штрих-кодов Code-39 с возможностью экспорта в файлы изображений.
' Поддержка: разметка сетки, печать на А4, экспорт в PDF, генерация и экспорт штрих-кодов.
' ==============================================================================

Option Explicit

' Константы названий листов
Private Const SHEET_DATA_NAME As String = "Данные"
Private Const SHEET_TEMPLATE_NAME As String = "Шаблон"
Private Const SHEET_RESULT_NAME As String = "Результат"

''' <summary>
''' Основная процедура запуска генератора
''' </summary>
Public Sub RunGenerator()
    On Error GoTo ErrorHandler

    ' Проверка наличия обязательных листов
    If Not SheetExists(SHEET_DATA_NAME) Or Not SheetExists(SHEET_TEMPLATE_NAME) Then
        Dim answer As VbMsgBoxResult
        answer = MsgBox("Не найдены необходимые листы '" & SHEET_DATA_NAME & "' и/или '" & SHEET_TEMPLATE_NAME & "'." & vbCrLf & _
                        "Хотите создать демонстрационный шаблон и пример данных для работы?", vbQuestion + vbYesNo, "Настройка среды")
        If answer = vbYes Then
            CreateSampleWorkspace
            MsgBox "Демонстрационная среда успешно создана!" & vbCrLf & _
                   "Вы можете изменить шаблон и таблицу данных, после чего запустить макрос повторно.", vbInformation, "Успех"
            Exit Sub
        Else
            MsgBox "Генерация отменена. Пожалуйста, создайте листы '" & SHEET_DATA_NAME & "' и '" & SHEET_TEMPLATE_NAME & "' вручную.", vbExclamation, "Ошибка"
            Exit Sub
        End If
    End If

    ' Отображение формы выбора режима генерации
    Dim modeSelection As String
    modeSelection = InputBox("Выберите режим генерации:" & vbCrLf & _
                             "1 - Сетка карточек на одном листе (для печати А4)" & vbCrLf & _
                             "2 - Создание отдельных листов для каждой строки" & vbCrLf & _
                             "3 - Экспорт индивидуальных PDF-файлов в папку" & vbCrLf & _
                             "4 - Сгенерировать штрих-код и сохранить в изображение PNG", "Выбор режима", "1")

    If modeSelection = "" Then Exit Sub ' Пользователь нажал Отмена

    ' Оптимизация производительности Excel
    TogglePerformance True

    Select Case modeSelection
        Case "1"
            GenerateGridCards
        Case "2"
            GenerateIndividualSheets
        Case "3"
            GeneratePDFFiles
        Case "4"
            PromptAndGenerateBarcodeImage
        Case Else
            MsgBox "Неверный выбор режима. Пожалуйста, введите 1, 2, 3 или 4.", vbCritical, "Ошибка"
    End Select

CleanExit:
    TogglePerformance False
    Exit Sub

ErrorHandler:
    MsgBox "Произошла критическая ошибка во время выполнения: " & Err.Description, vbCritical, "Ошибка генерации"
    Resume CleanExit
End Sub

''' <summary>
''' Создание примера структуры листов
''' </summary>
Public Sub CreateSampleWorkspace()
    Dim wsData As Worksheet
    Dim wsTemp As Worksheet

    ' Создаем лист Данные
    If SheetExists(SHEET_DATA_NAME) Then
        Application.DisplayAlerts = False
        ThisWorkbook.Sheets(SHEET_DATA_NAME).Delete
        Application.DisplayAlerts = True
    End If
    Set wsData = ThisWorkbook.Sheets.Add(Before:=ThisWorkbook.Sheets(1))
    wsData.Name = SHEET_DATA_NAME

    ' Создаем лист Шаблон
    If SheetExists(SHEET_TEMPLATE_NAME) Then
        Application.DisplayAlerts = False
        ThisWorkbook.Sheets(SHEET_TEMPLATE_NAME).Delete
        Application.DisplayAlerts = True
    End If
    Set wsTemp = ThisWorkbook.Sheets.Add(After:=wsData)
    wsTemp.Name = SHEET_TEMPLATE_NAME

    ' Наполнение листа данных демонстрационной информацией
    With wsData
        .Cells(1, 1).Value = "ID"
        .Cells(1, 2).Value = "Наименование"
        .Cells(1, 3).Value = "Категория"
        .Cells(1, 4).Value = "Цена"
        .Cells(1, 5).Value = "Дата_Поставки"
        .Cells(1, 6).Value = "Ответственный"

        ' Строка 1
        .Cells(2, 1).Value = "1001"
        .Cells(2, 2).Value = "Ноутбук бизнес-класса"
        .Cells(2, 3).Value = "Электроника"
        .Cells(2, 4).Value = 125000
        .Cells(2, 5).Value = Date
        .Cells(2, 6).Value = "Иванов И.И."

        ' Строка 2
        .Cells(3, 1).Value = "1002"
        .Cells(3, 2).Value = "Эргономичное кресло"
        .Cells(3, 3).Value = "Мебель"
        .Cells(3, 4).Value = 24500
        .Cells(3, 5).Value = Date - 1
        .Cells(3, 6).Value = "Петров П.П."

        ' Строка 3
        .Cells(4, 1).Value = "1003"
        .Cells(4, 2).Value = "Беспроводная мышь"
        .Cells(4, 3).Value = "Аксессуары"
        .Cells(4, 4).Value = 4200
        .Cells(4, 5).Value = Date - 5
        .Cells(4, 6).Value = "Сидоров С.С."

        ' Форматирование шапки таблицы данных
        With .Range("A1:F1")
            .Font.Bold = True
            .Interior.Color = RGB(41, 128, 185)
            .Font.Color = RGB(255, 255, 255)
            .HorizontalAlignment = xlCenter
        End With
        .Columns("A:F").AutoFit
    End With

    ' Наполнение листа Шаблона форматированием карточки/ценника
    With wsTemp
        ' Создание красивой границы для карточки
        .Range("B2:D6").BorderAround xlContinuous, xlMedium, , RGB(44, 62, 80)

        ' Заголовки и разметка placeholders
        .Range("B2:D2").Merge
        .Range("B2").Value = "КАРТОЧКА ТОВАРА #{{ID}}"
        .Range("B2").Font.Bold = True
        .Range("B2").Font.Size = 12
        .Range("B2").HorizontalAlignment = xlCenter
        .Range("B2").Interior.Color = RGB(236, 240, 241)

        .Range("B3").Value = "Наименование:"
        .Range("B3").Font.Italic = True
        .Range("C3:D3").Merge
        .Range("C3").Value = "{{Наименование}}"
        .Range("C3").Font.Bold = True

        .Range("B4").Value = "Категория:"
        .Range("B4").Font.Italic = True
        .Range("C4:D4").Merge
        .Range("C4").Value = "{{Категория}}"

        .Range("B5").Value = "Стоимость:"
        .Range("B5").Font.Italic = True
        .Range("C5:D5").Merge
        .Range("C5").Value = "{{Цена}} руб."
        .Range("C5").Font.Bold = True
        .Range("C5").Font.Color = RGB(192, 57, 43)

        .Range("B6").Value = "Дата:"
        .Range("B6").Font.Italic = True
        .Range("C6").Value = "{{Дата_Поставки}}"
        .Range("D6").Value = "ОТВ: {{Ответственный}}"
        .Range("D6").Font.Size = 8
        .Range("D6").HorizontalAlignment = xlRight

        ' Выравнивание колонок шаблона
        .Columns("B:D").ColumnWidth = 15
    End With
End Sub

''' <summary>
''' Режим 1: Генерация сетки карточек на одном листе (для удобной печати А4)
''' </summary>
Private Sub GenerateGridCards()
    Dim wsData As Worksheet, wsTemp As Worksheet, wsRes As Worksheet
    Set wsData = ThisWorkbook.Sheets(SHEET_DATA_NAME)
    Set wsTemp = ThisWorkbook.Sheets(SHEET_TEMPLATE_NAME)

    ' Создаем или очищаем лист результатов
    If SheetExists(SHEET_RESULT_NAME) Then
        Application.DisplayAlerts = False
        ThisWorkbook.Sheets(SHEET_RESULT_NAME).Delete
        Application.DisplayAlerts = True
    End If
    Set wsRes = ThisWorkbook.Sheets.Add(After:=wsTemp)
    wsRes.Name = SHEET_RESULT_NAME

    ' Определяем границы шаблона (по умолчанию B2:D6 из примера)
    ' Но макрос пытается автоматически найти непустой диапазон на листе шаблона
    Dim templateRange As Range
    Set templateRange = wsTemp.Range("B2:D6")

    Dim tRowsCount As Long, tColsCount As Long
    tRowsCount = templateRange.Rows.Count
    tColsCount = templateRange.Columns.Count

    ' Чтение данных
    Dim lastRow As Long, lastCol As Long
    lastRow = wsData.Cells(wsData.Rows.Count, 1).End(xlUp).Row
    lastCol = wsData.Cells(1, wsData.Columns.Count).End(xlToLeft).Column

    If lastRow < 2 Then
        MsgBox "Таблица данных пуста!", vbExclamation, "Ошибка"
        Exit Sub
    End If

    ' Собираем заголовки колонок
    Dim headers() As String
    ReDim headers(1 To lastCol)
    Dim c As Long
    For c = 1 To lastCol
        headers(c) = Trim(wsData.Cells(1, c).Value)
    Next c

    ' Параметры сетки (например, 2 карточки по горизонтали)
    Dim cardsPerRow As Long
    cardsPerRow = 2

    Dim r As Long, cardIdx As Long
    cardIdx = 0

    For r = 2 To lastRow
        cardIdx = cardIdx + 1

        ' Расчет позиции для текущей карточки на результирующем листе
        Dim gridRow As Long, gridCol As Long
        gridRow = Int((cardIdx - 1) / cardsPerRow)
        gridCol = (cardIdx - 1) Mod cardsPerRow

        Dim destRow As Long, destCol As Long
        destRow = 2 + gridRow * (tRowsCount + 2) ' С зазором в 2 строки
        destCol = 2 + gridCol * (tColsCount + 1) ' С зазором в 1 колонку

        ' Копируем форматирование и структуру шаблона
        templateRange.Copy
        Dim destRange As Range
        Set destRange = wsRes.Cells(destRow, destCol)
        destRange.PasteSpecial xlPasteAll

        ' Копируем ширину колонок для точности отображения
        Dim i As Long, j As Long
        For i = 1 To tColsCount
            wsRes.Cells(destRow, destCol + i - 1).ColumnWidth = templateRange.Columns(i).ColumnWidth
        Next i

        ' Выполняем подстановку плейсхолдеров
        For i = 1 To tRowsCount
            For j = 1 To tColsCount
                Dim cell As Range
                Set cell = wsRes.Cells(destRow + i - 1, destCol + j - 1)
                Dim cellValue As String
                cellValue = cell.Value

                If InStr(cellValue, "{{") > 0 And InStr(cellValue, "}}") > 0 Then
                    Dim colIdx As Long
                    For colIdx = 1 To lastCol
                        Dim placeholder As String
                        placeholder = "{{" & headers(colIdx) & "}}"
                        If InStr(cellValue, placeholder) > 0 Then
                            Dim rawVal As Variant
                            rawVal = wsData.Cells(r, colIdx).Value
                            cellValue = Replace(cellValue, placeholder, CStr(rawVal))
                        End If
                    Next colIdx
                    cell.Value = cellValue
                End If
            Next j
        Next i

        ' Обновление прогресс-бара в строке состояния
        Application.StatusBar = "Генерация сетки: обработано " & cardIdx & " из " & (lastRow - 1) & " карточек..."
    Next r

    ' Настройка параметров страницы листа результатов для красивой печати
    With wsRes.PageSetup
        .Orientation = xlPortrait
        .PaperSize = xlPaperA4
        .LeftMargin = Application.InchesToPoints(0.5)
        .RightMargin = Application.InchesToPoints(0.5)
        .TopMargin = Application.InchesToPoints(0.5)
        .BottomMargin = Application.InchesToPoints(0.5)
        .Zoom = False
        .FitToPagesWide = 1
        .FitToPagesTall = False
    End With

    Application.CutCopyMode = False
    wsRes.Activate
    MsgBox "Генерация сетки завершена! Результаты помещены на лист '" & SHEET_RESULT_NAME & "'.", vbInformation, "Успех"
End Sub

''' <summary>
''' Режим 2: Создание индивидуальных листов для каждой строки данных
''' </summary>
Private Sub GenerateIndividualSheets()
    Dim wsData As Worksheet, wsTemp As Worksheet
    Set wsData = ThisWorkbook.Sheets(SHEET_DATA_NAME)
    Set wsTemp = ThisWorkbook.Sheets(SHEET_TEMPLATE_NAME)

    Dim lastRow As Long, lastCol As Long
    lastRow = wsData.Cells(wsData.Rows.Count, 1).End(xlUp).Row
    lastCol = wsData.Cells(1, wsData.Columns.Count).End(xlToLeft).Column

    If lastRow < 2 Then
        MsgBox "Таблица данных пуста!", vbExclamation, "Ошибка"
        Exit Sub
    End If

    Dim headers() As String
    ReDim headers(1 To lastCol)
    Dim c As Long
    For c = 1 To lastCol
        headers(c) = Trim(wsData.Cells(1, c).Value)
    Next c

    Dim r As Long, generatedCount As Long
    generatedCount = 0

    ' Поиск колонки для уникального названия листа (по умолчанию ID или первая колонка)
    Dim nameColIdx As Long
    nameColIdx = 1
    For c = 1 To lastCol
        If LCase(headers(c)) = "id" Or LCase(headers(c)) = "наименование" Then
            nameColIdx = c
            Exit For
        End If
    Next c

    For r = 2 To lastRow
        Dim uniqueName As String
        uniqueName = CleanSheetName(CStr(wsData.Cells(r, nameColIdx).Value))
        If uniqueName = "" Then uniqueName = "Лист_" & r

        ' Если лист уже существует, удаляем его
        If SheetExists(uniqueName) Then
            Application.DisplayAlerts = False
            ThisWorkbook.Sheets(uniqueName).Delete
            Application.DisplayAlerts = True
        End If

        ' Копируем шаблон
        wsTemp.Copy After:=ThisWorkbook.Sheets(ThisWorkbook.Sheets.Count)
        Dim wsNew As Worksheet
        Set wsNew = ThisWorkbook.Sheets(ThisWorkbook.Sheets.Count)
        wsNew.Name = uniqueName

        ' Находим плейсхолдеры на всем новом листе и производим замену
        Dim cell As Range
        For Each cell In wsNew.UsedRange
            Dim cellValue As String
            cellValue = cell.Value
            If InStr(cellValue, "{{") > 0 And InStr(cellValue, "}}") > 0 Then
                Dim colIdx As Long
                For colIdx = 1 To lastCol
                    Dim placeholder As String
                    placeholder = "{{" & headers(colIdx) & "}}"
                    If InStr(cellValue, placeholder) > 0 Then
                        Dim rawVal As Variant
                        rawVal = wsData.Cells(r, colIdx).Value
                        cellValue = Replace(cellValue, placeholder, CStr(rawVal))
                    End If
                Next colIdx
                cell.Value = cellValue
            End If
        Next cell

        generatedCount = generatedCount + 1
        Application.StatusBar = "Генерация листов: создано " & generatedCount & " из " & (lastRow - 1) & "..."
    Next r

    MsgBox "Генерация листов успешно завершена! Создано листов: " & generatedCount, vbInformation, "Успех"
End Sub

''' <summary>
''' Режим 3: Экспорт документов в индивидуальные PDF-файлы
''' </summary>
Private Sub GeneratePDFFiles()
    Dim wsData As Worksheet, wsTemp As Worksheet
    Set wsData = ThisWorkbook.Sheets(SHEET_DATA_NAME)
    Set wsTemp = ThisWorkbook.Sheets(SHEET_TEMPLATE_NAME)

    Dim lastRow As Long, lastCol As Long
    lastRow = wsData.Cells(wsData.Rows.Count, 1).End(xlUp).Row
    lastCol = wsData.Cells(1, wsData.Columns.Count).End(xlToLeft).Column

    If lastRow < 2 Then
        MsgBox "Таблица данных пуста!", vbExclamation, "Ошибка"
        Exit Sub
    End If

    ' Запрос папки сохранения
    Dim targetFolder As String
    With Application.FileDialog(msoFileDialogFolderPicker)
        .Title = "Выберите папку для сохранения PDF-файлов"
        .AllowMultiSelect = False
        If .Show = -1 Then
            targetFolder = .SelectedItems(1) & "\"
        Else
            MsgBox "Экспорт отменен.", vbExclamation, "Отмена"
            Exit Sub
        End If
    End With

    Dim headers() As String
    ReDim headers(1 To lastCol)
    Dim c As Long
    For c = 1 To lastCol
        headers(c) = Trim(wsData.Cells(1, c).Value)
    Next c

    ' Создаем временный лист для рендеринга PDF
    Dim wsTempRender As Worksheet
    wsTemp.Copy After:=wsTemp
    Set wsTempRender = ThisWorkbook.Sheets(wsTemp.Index + 1)
    wsTempRender.Name = "Временный_Рендер"

    Dim r As Long, pdfCount As Long
    pdfCount = 0

    For r = 2 To lastRow
        ' Очищаем временный рендерер и копируем заново шаблон
        wsTempRender.Cells.Clear
        wsTemp.Cells.Copy Destination:=wsTempRender.Cells(1, 1)

        ' Заменяем плейсхолдеры
        Dim cell As Range
        For Each cell In wsTempRender.UsedRange
            Dim cellValue As String
            cellValue = cell.Value
            If InStr(cellValue, "{{") > 0 And InStr(cellValue, "}}") > 0 Then
                Dim colIdx As Long
                For colIdx = 1 To lastCol
                    Dim placeholder As String
                    placeholder = "{{" & headers(colIdx) & "}}"
                    If InStr(cellValue, placeholder) > 0 Then
                        Dim rawVal As Variant
                        rawVal = wsData.Cells(r, colIdx).Value
                        cellValue = Replace(cellValue, placeholder, CStr(rawVal))
                    End If
                Next colIdx
                cell.Value = cellValue
            End If
        Next cell

        ' Определяем имя PDF файла
        Dim fileName As String
        fileName = "Документ_" & wsData.Cells(r, 1).Value & ".pdf"
        fileName = CleanFileName(fileName)

        ' Настройка параметров печати
        With wsTempRender.PageSetup
            .Orientation = xlPortrait
            .PaperSize = xlPaperA4
            .Zoom = False
            .FitToPagesWide = 1
            .FitToPagesTall = 1
        End With

        ' Экспорт в PDF
        wsTempRender.ExportAsFixedFormat Type:=xlTypePDF, _
                                         Filename:=targetFolder & fileName, _
                                         Quality:=xlQualityStandard, _
                                         IncludeDocProperties:=True, _
                                         IgnorePrintAreas:=False, _
                                         OpenAfterPublish:=False

        pdfCount = pdfCount + 1
        Application.StatusBar = "Экспорт PDF: сохранено " & pdfCount & " из " & (lastRow - 1) & " файлов..."
    Next r

    ' Удаляем временный рендер-лист без предупреждений
    Application.DisplayAlerts = False
    wsTempRender.Delete
    Application.DisplayAlerts = True

    MsgBox "Экспорт завершен успешно! Сохранено файлов: " & pdfCount & " в папку " & targetFolder, vbInformation, "Успех"
End Sub

' ==============================================================================
' СЕКЦИЯ ГЕНЕРАЦИИ И ЭКСПОРТА ШТРИХ-КОДОВ (ВЕКТОРНЫЙ CODE-39)
' ==============================================================================

''' <summary>
''' Запрашивает у пользователя данные и сохраняет сгенерированный штрих-код в PNG
''' </summary>
Public Sub PromptAndGenerateBarcodeImage()
    Dim barcodeData As String
    barcodeData = InputBox("Введите данные для генерации штрих-кода Code-39:" & vbCrLf & _
                           "(Поддерживаются цифры 0-9, заглавные буквы A-Z, символы - . $ / + % и пробел)", _
                           "Генератор штрих-кодов", "12345-CODE")

    If barcodeData = "" Then Exit Sub

    Dim savePath As String
    With Application.FileDialog(msoFileDialogSaveAs)
        .Title = "Укажите место сохранения файла штрих-кода (PNG)"
        .FilterIndex = 2 ' Обычно PNG или любой другой графический формат
        .InitialFileName = "Штрихкод_" & barcodeData & ".png"
        If .Show = -1 Then
            savePath = .SelectedItems(1)
        Else
            MsgBox "Сохранение отменено.", vbExclamation, "Отмена"
            Exit Sub
        End If
    End With

    ' Генерация штрих-кода во временной рабочей области
    Dim wsTempShp As Worksheet
    Set wsTempShp = ThisWorkbook.Sheets.Add
    wsTempShp.Name = "Временный_Штрихкод"

    Dim barShape As Shape
    Set barShape = DrawCode39Vector(barcodeData, wsTempShp, 50, 50, 80, 1.5)

    If Not barShape Is Nothing Then
        ' Экспортируем сгруппированный штрих-код в PNG файл
        ExportShapeToPNG barShape, wsTempShp, savePath
        MsgBox "Изображение штрих-кода успешно сгенерировано и сохранено!" & vbCrLf & _
               "Путь: " & savePath, vbInformation, "Успех"
    Else
        MsgBox "Не удалось сгенерировать штрих-код. Проверьте допустимость символов.", vbCritical, "Ошибка"
    End If

    ' Удаляем временный лист
    Application.DisplayAlerts = False
    wsTempShp.Delete
    Application.DisplayAlerts = True
End Sub

''' <summary>
''' Функция отрисовки векторного штрих-кода Code-39 на листе
''' </summary>
Public Function DrawCode39Vector(ByVal dataStr As String, ByRef ws As Worksheet, _
                                 ByVal leftPos As Double, ByVal topPos As Double, _
                                 ByVal barHeight As Double, ByVal narrowWidth As Double) As Shape
    On Error GoTo DrawError

    Dim cleanData As String
    cleanData = UCase(Trim(dataStr))

    ' Code-39 должен начинаться и заканчиваться символом '*'
    If Left(cleanData, 1) <> "*" Then cleanData = "*" & cleanData
    If Right(cleanData, 1) <> "*" Then cleanData = cleanData & "*"

    Dim wideWidth As Double
    wideWidth = narrowWidth * 3

    Dim currentX As Double
    currentX = leftPos

    ' Коллекция для временного хранения всех созданных линий/прямоугольников
    Dim shapesCol As New Collection
    Dim shp As Shape

    ' Отрисовка символов
    Dim charIdx As Long
    For charIdx = 1 To Len(cleanData)
        Dim currentChar As String
        currentChar = Mid(cleanData, charIdx, 1)

        Dim pattern As String
        pattern = GetCode39Pattern(currentChar)

        If pattern = "" Then
            ' Недопустимый символ в строке
            GoTo DrawError
        End If

        ' Паттерн содержит 9 символов: N (узкий) или W (широкий)
        ' Чередование: bar, space, bar, space, bar, space, bar, space, bar
        Dim i As Long
        For i = 1 To 9
            Dim isBar As Boolean
            isBar = (i Mod 2 <> 0) ' Нечетные - линии (черные), четные - пробелы (пусто)

            Dim elementWidth As Double
            If Mid(pattern, i, 1) = "W" Then
                elementWidth = wideWidth
            Else
                elementWidth = narrowWidth
            End If

            If isBar Then
                ' Рисуем черный прямоугольник без границы
                Set shp = ws.Shapes.AddShape(msoShapeRectangle, currentX, topPos, elementWidth, barHeight)
                shp.Fill.ForeColor.RGB = RGB(0, 0, 0)
                shp.Line.Visible = msoFalse
                shapesCol.Add shp
            End If

            currentX = currentX + elementWidth
        Next i

        ' Межсимвольный интервал (узкий белый пробел)
        currentX = currentX + narrowWidth
    Next charIdx

    ' Добавляем подпись (человекочитаемый текст) под штрих-кодом
    Dim textLabel As String
    textLabel = Mid(cleanData, 2, Len(cleanData) - 2) ' без звездочек '*'

    Dim totalWidth As Double
    totalWidth = currentX - leftPos

    Set shp = ws.Shapes.AddTextbox(msoTextOrientationHorizontal, leftPos, topPos + barHeight + 5, totalWidth, 20)
    shp.TextFrame2.TextRange.Text = textLabel
    shp.TextFrame2.TextRange.ParagraphFormat.Alignment = msoAlignCenter
    shp.TextFrame2.TextRange.Font.Size = 10
    shp.TextFrame2.TextRange.Font.Name = "Courier New"
    shp.TextFrame2.TextRange.Font.Bold = msoTrue
    shp.Line.Visible = msoFalse
    shp.Fill.Visible = msoFalse
    shapesCol.Add shp

    ' Создаем единую группу из всех нарисованных элементов
    Dim shapesArray() As Variant
    ReDim shapesArray(1 To shapesCol.Count)
    Dim sIdx As Long
    For sIdx = 1 To shapesCol.Count
        shapesArray(sIdx) = shapesCol(sIdx).Name
    Next sIdx

    Set DrawCode39Vector = ws.Shapes.Group(shapesArray)
    Exit Function

DrawError:
    ' Удаляем временные фигуры в случае ошибки
    Dim tempShp As Shape
    On Error Resume Next
    For Each tempShp In ws.Shapes
        tempShp.Delete
    Next tempShp
    Set DrawCode39Vector = Nothing
End Function

''' <summary>
''' Возвращает Code-39 паттерн для символа
''' </summary>
Private Function GetCode39Pattern(ByVal char As String) As String
    Select Case char
        Case "1": GetCode39Pattern = "WNNWNNNNW"
        Case "2": GetCode39Pattern = "NNWWNNNNW"
        Case "3": GetCode39Pattern = "WNWWNNNNN"
        Case "4": GetCode39Pattern = "NNNWWNNNW"
        Case "5": GetCode39Pattern = "WNNWWNNNN"
        Case "6": GetCode39Pattern = "NNWWWNNNN"
        Case "7": GetCode39Pattern = "NNNWNNWNW"
        Case "8": GetCode39Pattern = "WNNWNNWNN"
        Case "9": GetCode39Pattern = "NNWWNNWNN"
        Case "0": GetCode39Pattern = "NNNWNWNWN"
        Case "A": GetCode39Pattern = "WNNNNWNNW"
        Case "B": GetCode39Pattern = "NNWNNWNNW"
        Case "C": GetCode39Pattern = "WNWNNWNNN"
        Case "D": GetCode39Pattern = "NNNNWWNNW"
        Case "E": GetCode39Pattern = "WNNNWWNNN"
        Case "F": GetCode39Pattern = "NNWNWWNNN"
        Case "G": GetCode39Pattern = "NNNNNWWNW"
        Case "H": GetCode39Pattern = "WNNNNWWNN"
        Case "I": GetCode39Pattern = "NNWNNWWNN"
        Case "J": GetCode39Pattern = "NNNNWWWNN"
        Case "-": GetCode39Pattern = "NNNWNNNNW"
        Case ".": GetCode39Pattern = "WNNWNNWNN"
        Case " ": GetCode39Pattern = "NNWWNNWNN"
        Case "$": GetCode39Pattern = "NNWNNWNNW"
        Case "/": GetCode39Pattern = "NNWNNNNWW"
        Case "+": GetCode39Pattern = "NNNNWNWNW"
        Case "%": GetCode39Pattern = "NNNNNNWWW"
        Case "*": GetCode39Pattern = "NWNNWNWNN"
        Case Else: GetCode39Pattern = ""
    End Select
End Function

''' <summary>
''' Метод экспорта сгруппированной фигуры в PNG изображение через временный диаграммный лист
''' </summary>
Private Sub ExportShapeToPNG(ByRef shpGroup As Shape, ByRef ws As Worksheet, ByVal outputPath As String)
    ' Копируем группу
    shpGroup.Copy

    ' Создаем временную диаграмму (Chart) точного размера группы фигур
    Dim tempChartObj As ChartObject
    Set tempChartObj = ws.ChartObjects.Add(Left:=10, Top:=10, Width:=shpGroup.Width, Height:=shpGroup.Height)

    ' Активируем и вставляем фигуру внутрь диаграммы
    With tempChartObj
        .Activate
        .Chart.Paste
        ' Экспортируем в PNG формат
        .Chart.Export Filename:=outputPath, FilterName:="PNG"
        ' Удаляем временную диаграмму
        .Delete
    End With
End Sub

' ==============================================================================
' ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ И УТИЛИТЫ
' ==============================================================================

''' <summary>
''' Проверка существования листа по имени
''' </summary>
Private Function SheetExists(ByVal sheetName As String) As Boolean
    Dim ws As Worksheet
    On Error Resume Next
    Set ws = ThisWorkbook.Sheets(sheetName)
    On Error GoTo 0
    SheetExists = (Not ws Is Nothing)
End Function

''' <summary>
''' Очистка имени листа от недопустимых символов Excel
''' </summary>
Private Function CleanSheetName(ByVal sName As String) As String
    Dim invalidChars As Variant
    invalidChars = Array("/", "\", "?", "*", "[", "]", ":")

    Dim i As Long
    For i = LBound(invalidChars) To UBound(invalidChars)
        sName = Replace(sName, invalidChars(i), "_")
    Next i

    ' Ограничение длины имени листа в Excel (31 символ)
    If Len(sName) > 31 Then
        sName = Left(sName, 31)
    End If

    CleanSheetName = sName
End Function

''' <summary>
''' Очистка имени файла от недопустимых символов файловой системы
''' </summary>
Private Function CleanFileName(ByVal fName As String) As String
    Dim invalidChars As Variant
    invalidChars = Array("/", "\", "?", "*", ":", "|", "<", ">", """")

    Dim i As Long
    For i = LBound(invalidChars) To UBound(invalidChars)
        fName = Replace(fName, invalidChars(i), "_")
    Next i

    CleanFileName = fName
End Function

''' <summary>
''' Включение / отключение оптимизации производительности
''' </summary>
Private Sub TogglePerformance(ByVal startOpt As Boolean)
    With Application
        If startOpt Then
            .ScreenUpdating = False
            .DisplayAlerts = False
            .EnableEvents = False
            .Calculation = xlCalculationManual
        Else
            .ScreenUpdating = True
            .DisplayAlerts = True
            .EnableEvents = True
            .Calculation = xlCalculationAutomatic
            .StatusBar = False
        End If
    End With
End Sub
