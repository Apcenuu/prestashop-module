# Customers

## Адреса корпоративных клиентов

Клиент считается корпоративным, если в его `invoice address` поле `company` не пустое.

При выгрузке в CRM в этом случае создается корпортаивный клиент и привязаный к нему клиент типа `Контактное лицо`.

Если при выгрузке в CRM обнаружено, что создаваемый адрес в корпоративном клиенте в CRM уже существует (проверка по `externalId` и по совпадению поля `text`), то адрес в CRM редактируется (не создается новый).

Для названия адреса корпортаивного клиента в CRM используется поле `alias` (если оно заполнено), либо поле `company`.

При создании заказа в CMS для того же клиента, но с новым названием компании, создается новая компания в первом найденном в CRM корпоративном клиенте с этим контактным лицом.