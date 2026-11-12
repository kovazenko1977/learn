<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CRM-система для управления заявками на обслуживание и ремонт</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

  <div id="appRoot">
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center;">
      <div style="font-size: 16px; font-weight: 500; color: #64748b;">
        <i class="fa fa-spinner fa-spin"></i> Загрузка CRM системы...
      </div>
    </div>
  </div>

  <div class="toast-container" id="toastContainer"></div>

  <div class="modal-backdrop hidden" id="createTicketModal">
    <div class="modal-dialog modal-dialog-lg">
      <div class="modal-header">
        <div class="modal-title"><i class="fa fa-plus-circle"></i> Новая заявка на обслуживание</div>
        <button class="btn-icon" onclick="App.closeModal('createTicketModal')"><i class="fa fa-times"></i></button>
      </div>
      <form id="createTicketForm" onsubmit="App.handleCreateTicket(event)">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Заголовок / Краткая суть заявки <span style="color:var(--danger)">*</span></label>
            <input type="text" id="newTicketTitle" class="form-control" placeholder="Например: Протечка крана в санузле 3 этажа" required />
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
              <label class="form-label">Категория обслуживания <span style="color:var(--danger)">*</span></label>
              <select id="newTicketCategory" class="form-control" required>
                <option value="cat_plumbing">Сантехника и водоснабжение</option>
                <option value="cat_electric">Электрика и освещение</option>
                <option value="cat_hvac">Вентиляция и кондиционирование</option>
                <option value="cat_doors_windows">Двери, окна, замки</option>
                <option value="cat_furniture">Мебель и интерьер</option>
                <option value="cat_it_office">ИТ-оборудование и оргтехника</option>
                <option value="cat_safety">Пожарная и охранная безопасность</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Приоритет (SLA) <span style="color:var(--danger)">*</span></label>
              <select id="newTicketPriority" class="form-control" required>
                <option value="low">Низкий (SLA: 48 часов)</option>
                <option value="medium" selected>Средний (SLA: 24 часа)</option>
                <option value="high">Высокий (SLA: 8 часов)</option>
                <option value="critical">Критический (SLA: 2 часа)</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Подробное описание проблемы</label>
            <textarea id="newTicketDesc" class="form-control" rows="3" placeholder="Опишите детали неисправности или требуемых работ..."></textarea>
          </div>

          <div style="margin-top: 16px; padding-top: 14px; border-top: 1px dashed var(--border-color);">
            <div style="font-size: 13px; font-weight: 600; margin-bottom: 12px; color: var(--primary);">
              <i class="fa fa-sliders-h"></i> Дополнительные параметры (Конструктор форм)
            </div>
            <div id="dynamicFieldsContainer">
            </div>
          </div>

          <div class="form-group" style="margin-top: 16px;">
            <label class="form-label"><i class="fa fa-paperclip"></i> Прикрепить фото или документ</label>
            <input type="file" id="newTicketFile" class="form-control" />
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Разрешены JPG, PNG, PDF, DOC, XLS до 15 МБ</div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="App.closeModal('createTicketModal')">Отмена</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> Зарегистрировать заявку</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-backdrop hidden" id="ticketDetailModal">
    <div class="modal-dialog modal-dialog-lg">
      <div class="modal-header">
        <div class="modal-title"><i class="fa fa-file-alt"></i> Карточка заявки</div>
        <button class="btn-icon" onclick="App.closeModal('ticketDetailModal')"><i class="fa fa-times"></i></button>
      </div>
      <div class="modal-body" id="ticketDetailBody">
      </div>
    </div>
  </div>

  <div class="modal-backdrop hidden" id="massAssignModal">
    <div class="modal-dialog" style="max-width: 480px;">
      <div class="modal-header">
        <div class="modal-title"><i class="fa fa-users-cog"></i> Массовое назначение заявок</div>
        <button class="btn-icon" onclick="App.closeModal('massAssignModal')"><i class="fa fa-times"></i></button>
      </div>
      <form onsubmit="App.handleMassAssign(event)">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Выберите ответственного исполнителя для выбранных заявок:</label>
            <select id="massAssignExecutorSelect" class="form-control" required>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="App.closeModal('massAssignModal')">Отмена</button>
          <button type="submit" class="btn btn-primary">Назначить все выбранные</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-backdrop hidden" id="addFieldModal">
    <div class="modal-dialog" style="max-width: 520px;">
      <div class="modal-header">
        <div class="modal-title"><i class="fa fa-cube"></i> Добавить поле в конструктор</div>
        <button class="btn-icon" onclick="App.closeModal('addFieldModal')"><i class="fa fa-times"></i></button>
      </div>
      <form onsubmit="App.handleAddField(event)">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Системное имя (лат. идентификатор) <span style="color:var(--danger)">*</span></label>
            <input type="text" id="newFieldName" class="form-control" placeholder="floor_level" pattern="[a-zA-Z0-9_]+" required />
          </div>
          <div class="form-group">
            <label class="form-label">Название для пользователя (Label) <span style="color:var(--danger)">*</span></label>
            <input type="text" id="newFieldLabel" class="form-control" placeholder="Этаж / Уровень здания" required />
          </div>
          <div class="form-group">
            <label class="form-label">Тип поля</label>
            <select id="newFieldType" class="form-control">
              <option value="text">Текст (Однострочный)</option>
              <option value="number">Числовое поле</option>
              <option value="date">Дата</option>
              <option value="select">Выпадающий список</option>
              <option value="checkbox">Чекбокс (Флажок)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Подсказка в поле (Placeholder)</label>
            <input type="text" id="newFieldPlaceholder" class="form-control" placeholder="Например: 2 этаж" />
          </div>
          <div class="form-group">
            <label class="form-label">Варианты для списка (через запятую, если тип Select)</label>
            <input type="text" id="newFieldOptions" class="form-control" placeholder="Опция 1, Опция 2, Опция 3" />
          </div>
          <div class="form-group">
            <label style="display: flex; align-items: center; gap: 8px; font-size: 14px;">
              <input type="checkbox" id="newFieldRequired" />
              <span>Обязательно для заполнения</span>
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="App.closeModal('addFieldModal')">Отмена</button>
          <button type="submit" class="btn btn-primary">Добавить в форму</button>
        </div>
      </form>
    </div>
  </div>

  <div class="modal-backdrop hidden" id="createUserModal">
    <div class="modal-dialog" style="max-width: 520px;">
      <div class="modal-header">
        <div class="modal-title"><i class="fa fa-user-plus"></i> Новый пользователь</div>
        <button class="btn-icon" onclick="App.closeModal('createUserModal')"><i class="fa fa-times"></i></button>
      </div>
      <form onsubmit="App.handleCreateUser(event)">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Логин <span style="color:var(--danger)">*</span></label>
            <input type="text" id="newUserName" class="form-control" required />
          </div>
          <div class="form-group">
            <label class="form-label">ФИО сотрудника <span style="color:var(--danger)">*</span></label>
            <input type="text" id="newUserFio" class="form-control" required />
          </div>
          <div class="form-group">
            <label class="form-label">Пароль <span style="color:var(--danger)">*</span></label>
            <input type="password" id="newUserPassword" class="form-control" required />
          </div>
          <div class="form-group">
            <label class="form-label">Роль в системе</label>
            <select id="newUserRole" class="form-control">
              <option value="requester">Ответственный сотрудник (Заявитель)</option>
              <option value="executor">Исполнитель (Техник, слесарь)</option>
              <option value="manager">Начальник отдела</option>
              <option value="admin">Администратор</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" id="newUserEmail" class="form-control" />
          </div>
          <div class="form-group">
            <label class="form-label">Отдел / Служба</label>
            <input type="text" id="newUserDept" class="form-control" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="App.closeModal('createUserModal')">Отмена</button>
          <button type="submit" class="btn btn-primary">Создать аккаунт</button>
        </div>
      </form>
    </div>
  </div>

  <script src="assets/js/translations.js"></script>
  <script src="assets/js/app.js"></script>
</body>
</html>
