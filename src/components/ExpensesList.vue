<template  v-slot:body-cell-actions="props">
  <q-page padding>
    <q-dialog v-model="showEditModal">
      <q-card>
        <q-card-section>
          <q-form @submit="saveChangesEdit">
            <q-input
              v-model="editedItem.comment"
              label="Комментарий"
              type="text"
              :rules="[val => !!val || 'Поле обязательно для заполнения']"
              />
            <q-input
              v-model="editedItem.date"
              label="Дата"
              type="datetime-local"
              :rules="[val => !!val || 'Введите корректную дату']"
            />
            <q-input
              v-model="editedItem.sum"
              label="Сумма"
              type="number"
              :rules="[val => (val && val > 0) || 'Введите положительное число']"
            />
            <q-btn label="Сохранить" type="submit" color="primary" />
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>

    <q-dialog v-model="showAddModal">
      <q-card>
        <q-card-section>
          <q-form @submit="saveChangesAdd">
            <q-input
              v-model="addItem.comment"
              label="Комментарий"
              type="text"
              :rules="[val => !!val || 'Поле обязательно для заполнения']"
            />
            <q-input
              v-model="addItem.date"
              label="Дата"
              type="datetime-local"
              :rules="[val => !!val || 'Введите корректную дату']"
            />
            <q-input
              v-model="addItem.sum"
              label="Сумма"
              type="number"
              :rules="[val => (val && val > 0) || 'Введите положительное число']"
            />
            <q-btn label="Сохранить" type="submit" color="primary" />
          </q-form>
        </q-card-section>
      </q-card>
    </q-dialog>
    <q-btn style="width: 100%"  color="positive" @click="openAddModal()" >
      Добавить расход
    </q-btn>
    <q-table
      title="Список расходов"
      :rows="expenses"
      :columns="columns"
      row-key="id"
    >
    <template v-slot:body-cell-actions="props">
      <q-td :props="props">
        <q-btn  color="warning" @click="openEditModal(props.row)" >
          <q-icon name="edit" />
        </q-btn>

        <q-btn  style="margin-left: 10px;" color="negative" @click="deleteItem(props.row.id)" >
          <q-icon name="delete" />
        </q-btn>
      </q-td>
    </template>
    </q-table>

  </q-page>
</template>


<script>
import moment from "moment";

export default {
  data() {
    return {
      columns: [
        { name: 'comment', required: true, label: 'Название', align:'left',
        field: row=> row.comment, sortable: true},
        { name: 'sum', required: true, label: 'Цена', align:'left',
          field: row=> row.sum, sortable: true},
        { name: 'date', required: true, label: 'Дата', align:'left',
          field: row=> row.date, sortable: true},
        { name: "actions", required: true, label: "Действия", align: "left", field: "actions"}
      ],
      expenses: [],
      showEditModal: false,
      showAddModal: false,
      addItem : {
        comment: '',
        sum: '',
        date: '',
      },
    }
  },
  mounted() {
    this.fetchExpenses();
  },
  methods:{
    async fetchExpenses() {
      try {
        const response = await this.$axios.get('/api/test/expense');
        this.expenses = response.data;
        this.$q.notify({
          color: 'positive',
          position: 'bottom-right',
          message: 'Данные обновлены',
          icon: 'announcement'
        })
      } catch (error){
        this.$q.notify({
          color: 'negative',
          position: 'bottom-right',
          message: 'Ошибка при загрузке данных',
          icon: 'report_problem'
        })
      }
    },

    async deleteItem(itemId) {
      try {
        await this.$axios.delete(`/api/test/expense/${itemId}`);
        this.fetchExpenses();
      } catch (error) {
        this.$q.notify({
          color: 'negative',
          position: 'bottom-right',
          message: 'Ошибка удаления',
          icon: 'report_problem'
        })
      }
    },
    openEditModal(item) {
      this.editedItem = {...item};
      this.showEditModal = true;
    },
     openAddModal(item) {
      this.addItem = {...item};
      this.showAddModal = true;
    },
    // Функция для сохранения изменений
    async saveChangesEdit() {
      try {
        const formattedDatetime = moment(this.editedItem.datetime).format("YYYY-MM-DD HH:mm");
        await this.$axios.patch(`/api/test/expense/${this.editedItem.id}`, {
          comment: this.editedItem.comment,
          date: formattedDatetime,
          sum: this.editedItem.sum,
        });
        this.fetchExpenses(); // После успешного сохранения обновляем данные таблицы
        this.showEditModal = false; // Закрываем модальное окно
      } catch (error) {
        this.$q.notify({
          color: 'negative',
          position: 'bottom-right',
          message: `Ошибка при изменений данных: ${error.response.data.error}` ,
          icon: 'report_problem'
        })
      }
    },
    async saveChangesAdd() {
      try {
        let formData = new FormData();
        formData.append("comment", this.addItem.comment);
        const formattedDatetime = moment(this.addItem.datetime).format("YYYY-MM-DD HH:mm");
        formData.append("date", formattedDatetime);
        formData.append("sum", this.addItem.sum);

        await this.$axios.post(`/api/test/expense`, formData, {
          headers:{
            'Content-Type': 'multipart/form-data'
          }
        });

        this.fetchExpenses(); // После успешного сохранения обновляем данные таблицы
        this.showAddModal = false; // Закрываем модальное окно
      } catch (error) {
        this.$q.notify({
          color: 'negative',
          position: 'bottom-right',
          message: `Ошибка при добавлении данных: ${error.response.data.error}` ,
          icon: 'report_problem'
        })
      }
    },
  },
}
</script>
