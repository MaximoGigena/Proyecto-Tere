import RegistrarMascota from './registrarMascota.vue'

describe('<RegistrarMascota />', () => {
  it('renders', () => {
    // see: https://on.cypress.io/mounting-vue
    cy.mount(RegistrarMascota)
  })
})