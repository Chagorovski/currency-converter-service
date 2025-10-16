export class NetworkError extends Error {
    constructor(message, cause = null) {
        super(message)
        this.name = 'NetworkError'
        this.cause = cause
    }
}
