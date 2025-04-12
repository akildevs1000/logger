<template>
  <v-container>
    <v-card outlined>
      <v-card-text>
        <v-container>
          <v-row>
            <v-col>
              <div class="text-left">
                <v-menu
                  ref="from_menu"
                  v-model="from_menu"
                  :close-on-content-click="false"
                  :return-value.sync="from_date"
                  transition="scale-transition"
                  offset-y
                  min-width="auto"
                >
                  <template v-slot:activator="{ on, attrs }">
                    <v-text-field
                      label="From Date"
                      :hide-details="!payload.from_date"
                      outlined
                      dense
                      v-model="payload.from_date"
                      readonly
                      v-bind="attrs"
                      v-on="on"
                    ></v-text-field>
                  </template>
                  <v-date-picker
                    v-model="payload.from_date"
                    no-title
                    scrollable
                  >
                    <v-spacer></v-spacer>
                    <v-btn
                      class="blue-grey"
                      small
                      dark
                      @click="from_menu = false"
                    >
                      Cancel
                    </v-btn>
                    <v-btn
                      class="blue-grey darken-3"
                      small
                      dark
                      @click="$refs.from_menu.save(payload.from_date)"
                    >
                      OK
                    </v-btn>
                  </v-date-picker>
                </v-menu>
              </div>
            </v-col>
            <v-col>
              <div class="text-left">
                <v-menu
                  ref="to_menu"
                  v-model="to_menu"
                  :close-on-content-click="false"
                  :return-value.sync="to_date"
                  transition="scale-transition"
                  offset-y
                  min-width="auto"
                >
                  <template v-slot:activator="{ on, attrs }">
                    <v-text-field
                      label="To Date"
                      :hide-details="!payload.to_date"
                      outlined
                      dense
                      v-model="payload.to_date"
                      readonly
                      v-bind="attrs"
                      v-on="on"
                    ></v-text-field>
                  </template>
                  <v-date-picker v-model="payload.to_date" no-title scrollable>
                    <v-spacer></v-spacer>
                    <v-btn
                      class="blue-grey"
                      small
                      dark
                      @click="to_menu = false"
                    >
                      Cancel
                    </v-btn>
                    <v-btn
                      class="blue-grey darken-3"
                      small
                      dark
                      @click="$refs.to_menu.save(payload.to_date)"
                    >
                      OK
                    </v-btn>
                  </v-date-picker>
                </v-menu>
              </div>
            </v-col>
            <v-col>
              <div class="text-left">
                <v-text-field
                  label="User Id (optional)"
                  :hide-details="!payload.user_id"
                  @keyup="disabledExport"
                  outlined
                  dense
                  v-model="payload.user_id"
                ></v-text-field>
              </div>
              faaxio
            </v-col>
            <v-col class="text-right">
              <!-- <v-btn x-small
              :loading="loading"
              color="orange darken-3"
              dark
              @click="sync_record"
            >
              <v-icon small class="pr-1">mdi-sync</v-icon>
              Sync
            </v-btn> -->
              <v-btn
                x-small
                :loading="loading"
                color="blue darken-2"
                dark
                @click="filter_record"
              >
                <v-icon small class="pr-1">mdi-filter</v-icon>
                Filter
              </v-btn>
              <v-btn
                x-small
                :disabled="disabledExportButton"
                color="blue-grey darken-4"
                :loading="loading"
                dark
                @click="export_record"
              >
                <v-icon small class="pr-1">mdi-upload</v-icon>
                Export
              </v-btn>
            </v-col>

            <v-col cols="12 ">
              <v-data-table
                :headers="headers"
                :items="data"
                :server-items-length="total"
                :loading="loading"
                :options.sync="options"
                :footer-props="{
                  itemsPerPageOptions: [100, 200, 300, 400, 500],
                }"
              >
              </v-data-table>
            </v-col>
          </v-row>
        </v-container>
      </v-card-text>
    </v-card>
  </v-container>
</template>

<script>
export default {
  data: () => ({
    data: [],
    disabledExportButton: false,
    response: false,
    snackbar: false,
    dialog: false,
    from_date: null,
    from_menu: false,
    to_date: null,
    to_menu: false,
    Model: "Report",
    total: 0,
    headers: [
      {
        text: "Worker Number",
        align: "left",
        sortable: false,
        value: "user_id",
      },
      { text: "Log Date", align: "left", sortable: false, value: "log_date" },
      { text: "Log Time", align: "left", sortable: false, value: "log_time" },
      { text: "Type", align: "left", sortable: false, value: "type" },
      {
        text: "Device Location",
        align: "left",
        sortable: false,
        value: "device_id",
      },
      // {
      //   text: "Device Model",
      //   align: "left",
      //   sortable: false,
      //   value: "device_model",
      // },
    ],
    options: {},
    columns: {},
    payload: {
      per_page: 5,
      from_date: null,
      to_date: null,
      user_id: 0,
    },
  }),

  computed: {},

  watch: {
    dialog(val) {
      val || this.close();
      this.errors = [];
      this.search = "";
    },
    options: {
      handler() {
        this.getDataFromApi();
      },
      deep: true,
    },
  },
  created() {
    this.loading = false;

    let dt = new Date();
    let y = dt.getFullYear();
    let m = dt.getMonth() + 1;
    let d = dt.getDate();

    d = d < 10 ? "0" + d : d;
    m = m < 10 ? "0" + m : m;

    this.payload.from_date = `${y}-${m}-${d - 1}`;
    this.payload.to_date = `${y}-${m}-${d}`;
  },

  methods: {
    disabledExport() {
      this.disabledExportButton = true;
    },
    getDataFromApi() {
      const { page, itemsPerPage } = this.options;

      let options = {
        params: {
          per_page: itemsPerPage || 100,
          page: page,
          from: this.payload.from_date,
          to: this.payload.to_date,
          user_id: this.payload.user_id,
        },
      };

      this.$axios
        .get("http://localhost:8000/api/log", options)
        .then(({ data }) => {
          this.data = data.data;
          this.total = data.total;
        });
    },

    json_to_csv(json) {
      let str = "";
      json.map(
        (e) =>
          (str +=
            `${e.user_id}  ${e.log_date}  ${e.log_time}  ${e.type}  ${e.device_id}` +
            "\n")
      );
      return str;
    },
    sync_record() {
      this.$axios.post(`/sync_from_mdb`).then(({ data }) => {
        if (!data.status && data.status !== undefined) {
          alert(data.message);
          return;
        }
        this.getDataFromApi();
      });
    },
    filter_record() {
      const { page, itemsPerPage } = this.options;

      let payload = {
        params: {
          per_page: itemsPerPage || 100,
          page: page,
          from: this.payload.from_date,
          to: this.payload.to_date,
          user_id: this.payload.user_id,
        },
      };
      this.$axios.get(`/log`, payload).then(({ data }) => {
        this.data = data.data;
        this.total = data.total;

        if (this.data.length) {
          this.disabledExportButton = false;
        }
      });
    },

    export_record() {
      if (!this.data.length) {
        return;
      }
      let { from_date, to_date, user_id } = this.payload;
      let a = document.createElement("a");
      a.setAttribute(
        "href",
        `http://localhost:8000/api/export?from=${from_date}&to=${to_date}&user_id=${user_id}`
      );
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    },
  },
};
</script>
