<template>
  <div>
    <v-card class="pa-5">
      <v-row justify="left" align="left" class="pt-2 mt-5">
        <v-col cols="12" sm="8" md="4">
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
                <div class="mb-1">From Date</div>
                <v-text-field
                  :hide-details="!payload.from_date"
                  outlined
                  dense
                  v-model="payload.from_date"
                  readonly
                  v-bind="attrs"
                  v-on="on"
                ></v-text-field>
              </template>
              <v-date-picker v-model="payload.from_date" no-title scrollable>
                <v-spacer></v-spacer>
                <v-btn class="blue-grey" small dark @click="from_menu = false">
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
        <v-col cols="12" sm="8" md="4">
          <div class="mb-1">To Date</div>
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
                <v-btn class="blue-grey" small dark @click="to_menu = false">
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

        <v-col cols="12" sm="6" md="4" offset="0">
          <div class="mb-1">User Id</div>
          <div class="text-left">
            <v-text-field
              :hide-details="!payload.user_id"
              outlined
              dense
              v-model="payload.user_id"
            ></v-text-field>
          </div>
        </v-col>

        <v-col md="12">
          <div class="mb-5">
            <v-btn
              small
              :loading="loading"
              color="orange darken-3"
              dark
              @click="sync_record"
            >
              <v-icon small class="pr-1">mdi-file</v-icon>
              Sync records
            </v-btn>
            <v-btn
              small
              :loading="loading"
              color="blue darken-2"
              dark
              @click="filter_record"
            >
              <v-icon small class="pr-1">mdi-filter</v-icon>
              Filter records
            </v-btn>
            <v-btn
              :loading="loading"
              color="blue-grey darken-4"
              small
              dark
              @click="export_record"
            >
              <v-icon small class="pr-1">mdi-file</v-icon>
              Export records
            </v-btn>
          </div>
        </v-col>
      </v-row>
    </v-card>
    <v-row>
      <v-col cols="12">
        <v-data-table
          :headers="headers"
          :items="data"
          :server-items-length="total"
          :loading="loading"
          :options.sync="options"
          :footer-props="{
            itemsPerPageOptions: [5, 10, 15],
          }"
          class="elevation-1"
        >
        </v-data-table>
      </v-col>
    </v-row>
  </div>
</template>

<script>
export default {
  data: () => ({
    data: [],
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
      { text: "User Id", align: "left", sortable: false, value: "user_id" },
      { text: "Log Date", align: "left", sortable: false, value: "log_date" },
      { text: "Log Time", align: "left", sortable: false, value: "log_time" },
      { text: "Type", align: "left", sortable: false, value: "type" },
      { text: "Device Id", align: "left", sortable: false, value: "device_id" },
    ],
    options: {},
    payload: {
      per_page: 5,
      from_date: null,
      to_date: null,
      user_id: null,
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
    m = m < 10 ? "0" + m : m;
    this.payload.from_date = `${y}-${m}-01`;
    this.payload.to_date = `${y}-${m}-${31}`;
  },

  methods: {
    getDataFromApi(url = `http://127.0.0.1:8000/api/log`) {
      const { page, itemsPerPage } = this.options;

      let options = {
        params: {
          per_page: itemsPerPage,
          page: page,
        },
      };

      this.$axios.get(url, options).then(({ data }) => {
        this.data = data.data;
        this.total = data.total;
      });
    },

    json_to_csv(json) {
      let data = json.map((e) => ({
        "User Id": e.user_id,
        "Log Date": e.log_date,
        "Log Time": e.log_time,
        Type: e.type,
        "Device Id": e.device_id,
      }));

      let header = Object.keys(data[0]).join(",") + "\n";
      let rows = "";
      data.forEach((e) => {
        rows += Object.values(e).join(",").trim() + "\n";
      });
      return header + rows;
    },
    sync_record() {
      this.$axios.post(`http://127.0.0.1:8000/api/log`).then(({ data }) => {
        this.getDataFromApi();
      });
    },
    filter_record() {
      let options = {
        params: {
          from: this.payload.from_date,
          to: this.payload.to_date,
          user_id: this.payload.user_id,
        },
      };

      this.$axios
        .get(`http://127.0.0.1:8000/api/range`, options)
        .then(({ data }) => {
          this.data = data.data;
          this.total = data.total;
        });
    },

    export_record() {
      let data = this.data;
      if (data.length == 0) {
        this.snackbar = true;
        this.response = "No record to download";
        return;
      }
      let csvData = this.json_to_csv(data);
      let a = document.createElement("a");
      a.setAttribute(
        "href",
        "data:text/plain;charset=utf-8, " + encodeURIComponent(csvData)
      );
      a.setAttribute("download", "logs.txt");
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    },
  },
};
</script>
