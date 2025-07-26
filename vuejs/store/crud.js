import { defineStore } from 'pinia';
import axios from 'axios'
import { useCommonStore } from '@/store/common'

export const useCrudStore = defineStore({
    id: 'crud',
    state: () => ({
        getAllData: [],
        getAllDataPagination: [],
        getDataById: {},
        getPhoto: {},
        getSingleData: {},

        nextPage: null, 
        loading: false,
        allLoaded: false
    }),
    actions: {
        async getAll(api) {
            const token = localStorage.getItem('token');
            try {
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
                const response = await axios.get(process.env.VUE_APP_BASE_API_URL + api)
                this.getAllData = response.data.list
            } catch(e) {
                return e
            } finally {
                
            }
        },
        

        async getSingle(api) {
            const commonStore = useCommonStore()
            const token = localStorage.getItem('token');
            try {
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
                const response = await axios.get(process.env.VUE_APP_BASE_API_URL+api)
                this.getSingleData = response.data.single
            } catch(e) {
                return e
            }
            commonStore.loading = false
        },

        async getById(api, id) {
            const response = await axios.get(process.env.VUE_APP_BASE_API_URL+api+id)
            this.getDataById = response.data
            if(this.getDataById.media) {
                if(this.getDataById.media.length) {
                    this.getPhoto = this.getDataById.media[0].base_path+'/'+this.getDataById.media[0].file_name
                } else {
                    this.getPhoto = null
                }
            } else if(this.getDataById.product) {
                if(this.getDataById.product.media.length) {
                    this.getPhoto = this.getDataById.product.media[0].base_path+'/'+this.getDataById.product.media[0].file_name
                } else {
                    this.getPhoto = null
                }
            } else {
                this.getPhoto = null
            }
        },

        async newData(api, data) {
            try {
                const token = localStorage.getItem('token');
                axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
                return await axios.post(process.env.VUE_APP_BASE_API_URL+api, data)
            } catch(e) {
                return await e
            }
        },

        async updateData(api, data, config) {
            try {
                if(data.id) {
                    const token = localStorage.getItem('token');
                    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
                    return await axios.post(process.env.VUE_APP_BASE_API_URL+api+'/'+data.id, data, config)
                } else {
                    return await axios.post(process.env.VUE_APP_BASE_API_URL+api+'/'+data.get('id'), data, config)
                }
            } catch(e) {
                return await e
            }
        },

        async destroy(api, id) {
           try {
            const token = localStorage.getItem('token');
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
            return await axios.get(process.env.VUE_APP_BASE_API_URL+api+id)
           } catch(e) {
            return e
           }
        }
    }
});

